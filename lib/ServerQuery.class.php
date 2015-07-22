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

require 'lib/Gameserver.class.php';

/**
 * Main application class
 *
 * @author Steve Guidetti
 */
class ServerQuery {

    /**
     * Use the cache to store server data
     *
     * @var boolean
     */
    private $useCache = false;

    /**
     * Array of prepared Gameserver objects
     *
     * @var array
     */
    private $servers = array();

    public function __construct() {
        if(SQConfig::CACHE_ENABLE) {
            $this->useCache = is_dir('cache') && is_writable('cache');
        }
    }

    /**
     * Get the list of Gameserver objects
     * 
     * @return array
     */
    public function getServers() {
        return $this->servers;
    }

    /**
     * Execute main application logic
     */
    public function exec() {
        foreach(SQConfig::$servers as $server) {
            $this->servers[] = $this->getServerObject($server);
        }
    }

    /**
     * Get a Gameserver object based on a server config
     * 
     * @param array $server Element from the servers config
     * @return Gameserver
     */
    private function getServerObject(array $server) {
        $gs = self::initServerObject($server);
        if($this->useCache) {
            $cached = self::getFromCache($gs);
            if($cached) {
                return $cached;
            }
        }

        try {
            $gs->query();
        } catch(Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
        }

        if($this->useCache) {
            $this->updateCache($gs);
        }

        return $gs;
    }

    /**
     * Initialize a Gameserver object based on a server config
     * 
     * @param array $server Element from the servers config
     * @return Gameserver
     */
    private static function initServerObject(array $server) {
        $className = SQConfig::$games[$server['game']]['class'];
        if(!class_exists($className)) {
            $fileName = 'games/';
            $fileName .= substr($className, strrpos($className, '_') + 1);
            $fileName .= '.class.php';
            require $fileName;
        }

        return new $className($server['addr'], self::getServerConfig($server));
    }

    /**
     * Get the combined server config array
     * 
     * @param array $server Element from the servers config
     * @return array
     */
    private static function getServerConfig(array $server) {
        $config = array_key_exists('config', $server) ? $server['config'] : array();

        if(array_key_exists('config', SQConfig::$games[$server['game']])) {
            $config = array_merge(SQConfig::$games[$server['game']]['config'], $config);
        }

        return $config;
    }

    /**
     * Retrieve a Gameserver object from the cache
     * 
     * @param Gameserver $server
     * @return boolean|Gameserver Boolean false if object is not found or expired
     */
    private function getFromCache(Gameserver $server) {
        $fileName = self::getCacheFileName($server);

        if(!file_exists($fileName)) {
            return false;
        }

        if(time() - filemtime($fileName) > SQConfig::CACHE_TIME) {
            return false;
        }

        $data = file_get_contents($fileName);
        return unserialize($data);
    }

    /**
     * Store a Gameserver object in the cache
     * 
     * @param Gameserver $server
     */
    private function updateCache(Gameserver $server) {
        $fileName = self::getCacheFileName($server);

        $data = serialize($server);
        file_put_contents($fileName, $data);
    }

    /**
     * Get the name of a cache file based on a Gameserver object
     * 
     * @param Gameserver $server
     * @return string
     */
    private static function getCacheFileName(Gameserver $server) {
        $fileName = str_replace(':', '_', $server->getAddress());
        $fileName = 'cache/' . $fileName . '.dat';

        return $fileName;
    }

}
