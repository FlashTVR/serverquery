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

/**
 * Helper class for preparing output data for use in the HTML template
 *
 * @author Steve Guidetti
 */
class SQ_Template {

    /**
     * Get array of output data to use in the template
     * 
     * @param SQ_ServerQuery $sq Main application object populated with server data
     * @return mixed[]
     */
    public static function getTemplateData(SQ_ServerQuery $sq) {
        $servers = $sq->getServers();
        $serverOutput = array();
        foreach($servers as $gs) {
            $serverOutput[] = self::getServerTemplateData($gs);
        }

        return array(
            'servers' => $serverOutput,
            'stylesheet' => SQ_Config::WEB_PATH . 'serverquery.css',
        );
    }

    /**
     * Get object containing the output data for a single server
     * 
     * @param SQ_Gameserver $gs 
     * @return stdClass Anonymous object containing template values
     */
    private static function getServerTemplateData(SQ_Gameserver $gs) {
        $server = new stdClass();
        $server->online = $gs->isOnline();
        $server->error = $gs->getError();

        $gameId = $gs->getGameId();
        $server->gameId = $gameId;
        $server->gameName = self::cleanOutput(SQ_Config::$games[$gameId]['name']);
        $server->gameIcon = self::getGameImageURL($gameId);

        $server->addr = $gs->getAddress();
        $server->link = $gs->getConnectLink();
        $server->name = self::cleanOutput($gs->getName());
        $server->map = self::cleanOutput($gs->getMapName());
        $server->playerCount = $gs->getPlayerCount();
        $server->maxPlayers = $gs->getMaxPlayers();

        $server->players = $gs->getPlayerList();
        if($server->players !== null) {
            $server->players = array_map(array('self', 'cleanOutput'), $server->players);
        }

        return $server;
    }

    /**
     * Makes a string safe for HTML output
     * 
     * @param string $input
     * @return string
     */
    public static function cleanOutput($input) {
        return htmlspecialchars($input);
    }

    /**
     * Template helper to get the URL to a game icon
     * 
     * @param string $gameId Key from SQConfig::$games
     * @return string
     */
    private static function getGameImageURL($gameId) {
        return SQ_Config::WEB_PATH . 'img/games/' . $gameId . '.png';
    }

}
