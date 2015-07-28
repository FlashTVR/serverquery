<?php

/*
 * The MIT License
 *
 * Copyright 2015 Steve Guidetti.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require __DIR__ . '/Gameserver.class.php';

/**
 * Main application class
 *
 * @author Steve Guidetti
 */
class SQ_ServerQuery {

    /**
     * Use the cache to store server data
     *
     * @var bool
     */
    private $useCache = false;

    /**
     * Cron mode is active
     *
     * @var bool
     */
    private $cronMode = false;

    /**
     * Array of prepared Gameserver objects
     *
     * @var SQ_Gameserver[]
     */
    private $servers = array();

    /**
     * Constructor
     */
    public function __construct() {
        if(SQ_Config::CACHE_ENABLE) {
            $dir = __DIR__ . '/../cache';
            $this->useCache = is_dir($dir) && is_writable($dir);
            $this->cronMode = $this->useCache && SQ_Config::CRON_MODE;
        }
    }

    /**
     * Get the list of Gameserver objects
     * 
     * @return SQ_Gameserver[]
     */
    public function getServers() {
        return $this->servers;
    }

    /**
     * Get array of output data to use in the template
     * 
     * @return mixed[]
     */
    public function getTemplateData() {
        $servers = array();
        foreach($this->servers as $key => $gs) {
            $server = new stdClass();
            $server->online = $gs->isOnline();
            $server->error = $gs->getError();

            $gameId = SQ_Config::$servers[$key]['game'];
            $server->gameId = $gameId;
            $server->gameName = htmlspecialchars(SQ_Config::$games[$gameId]['name']);
            $server->gameIcon = self::getGameImageURL($gameId);

            $server->addr = $gs->getAddress();
            $server->link = $gs->getConnectLink();
            $server->name = htmlspecialchars($gs->getName());
            $server->map = htmlentities($gs->getMapName());
            $server->playerCount = $gs->getPlayerCount();
            $server->maxPlayers = $gs->getMaxPlayers();

            $server->players = $gs->getPlayerList();
            if($server->players !== null) {
                array_walk($server->players, 'htmlspecialchars');
            }

            $servers[] = $server;
        }

        return array(
            'servers' => $servers,
            'stylesheet' => SQ_Config::WEB_PATH . 'serverquery.css',
        );
    }

    /**
     * Template helper to get the URL to a game icon
     * 
     * @param string $gameId Key from SQConfig::$games
     * @return string
     */
    public static function getGameImageURL($gameId) {
        return SQ_Config::WEB_PATH . 'img/games/' . $gameId . '.png';
    }

    /**
     * Execute main application logic
     */
    public function exec() {
        foreach(SQ_Config::$servers as $server) {
            $update = !$this->cronMode;
            $this->servers[] = $this->getServerObject($server, $update);
        }
    }

    /**
     * Execute cron tasks
     * 
     * @param int $timeLimit Maximum execution time in seconds
     */
    public function cron($timeLimit = 60) {
        if($this->useCache) {
            shuffle(SQ_Config::$servers);

            $startTime = time();
            foreach(SQ_Config::$servers as $server) {
                if(time() - $startTime >= $timeLimit) {
                    return;
                }

                $this->getServerObject($server);
            }
        }
    }

    /**
     * Get a Gameserver object based on a server config
     * 
     * @param mixed[] $server Element from the servers config
     * @param bool $update Query server for updated status
     * @return SQ_Gameserver
     */
    private function getServerObject(array $server, $update = true) {
        $gs = self::initServerObject($server);
        if($this->useCache) {
            $cached = self::getFromCache($gs);
            if($cached) {
                $gs = $cached;
            }
            if(time() - $gs->getQueryTime() < SQ_Config::CACHE_TIME) {
                return $gs;
            }
        }

        if($update) {
            $gs->update();

            if($this->useCache) {
                $this->updateCache($gs);
            }
        }

        return $gs;
    }

    /**
     * Initialize a Gameserver object based on a server config
     * 
     * @param mixed[] $server Element from the servers config
     * @return SQ_Gameserver
     */
    private static function initServerObject(array $server) {
        $className = 'SQ_Game_' . SQ_Config::$games[$server['game']]['class'];
        if(!class_exists($className)) {
            $fileName = __DIR__ . '/games/';
            $fileName .= substr($className, strrpos($className, '_') + 1);
            $fileName .= '.class.php';
            require $fileName;
        }

        return new $className($server['game'], $server['addr'], self::getServerConfig($server));
    }

    /**
     * Get the combined server config array
     * 
     * @param mixed[] $server Element from the servers config
     * @return mixed[]
     */
    private static function getServerConfig(array $server) {
        $config = array_key_exists('config', $server) ? $server['config'] : array();

        if(array_key_exists('config', SQ_Config::$games[$server['game']])) {
            $config = array_merge(SQ_Config::$games[$server['game']]['config'], $config);
        }

        return $config;
    }

    /**
     * Retrieve a Gameserver object from the cache
     * 
     * @param SQ_Gameserver $server
     * @return SQ_Gameserver|false Boolean false if object is not found
     */
    private static function getFromCache(SQ_Gameserver $server) {
        $fileName = self::getCacheFileName($server);

        if(!file_exists($fileName)) {
            return false;
        }

        $data = file_get_contents($fileName);
        $gs = unserialize($data);

        if(!$gs || $gs->getConfig() !== $server->getConfig()) {
            return false;
        }

        return $gs;
    }

    /**
     * Store a Gameserver object in the cache
     * 
     * @param SQ_Gameserver $server
     */
    private static function updateCache(SQ_Gameserver $server) {
        $fileName = self::getCacheFileName($server);

        $data = serialize($server);
        file_put_contents($fileName, $data);
    }

    /**
     * Get the name of a cache file based on a Gameserver object
     * 
     * @param SQ_Gameserver $server
     * @return string
     */
    private static function getCacheFileName(SQ_Gameserver $server) {
        $fileName = __DIR__ . '/../cache/';
        $fileName .= $server->getGameId() . '_';
        $fileName .= str_replace(':', '_', $server->getAddress());
        $fileName .= '.dat';

        return $fileName;
    }

}
