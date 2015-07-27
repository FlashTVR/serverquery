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
 * Game: TShock (Terraria server)
 *
 * @author Steve Guidetti
 */
class Game_TShock extends Gameserver {

    protected $defaultConfig = array(
        /**
         * @var int Port used to query the server REST API
         */
        'queryPort' => 7878,
    );
    protected $port = 7777;

    protected function query($timeout) {
        $c = stream_context_create(array('http' => array('timeouut' => $timeout)));
        $res = @file_get_contents($this->getRestURL('/v2/server/status?players=true'), false, $c);
        if(!$res) {
            throw new Exception('REST request failed');
        }

        $obj = json_decode($res);
        if(!$obj) {
            throw new Exception('Invalid response from server');
        }

        $status = (int)$obj->status;
        if($status !== 200) {
            throw new Exception($obj->error, $status);
        }

        $this->setName($obj->name);
        $this->setPlayerCount($obj->playercount);
        $this->setMaxPlayers($obj->maxplayers);

        $players = array();
        foreach($obj->players as $player) {
            $players[] = $player->nickname;
        }
        $this->setPlayerList($players);
    }

    /**
     * Get the URL to a REST endpoint
     * 
     * @param string $endpoint
     * @return string
     */
    protected function getRestURL($endpoint) {
        return 'http://' . $this->getHostname() . ':' . $this->config['queryPort'] . $endpoint;
    }

}
