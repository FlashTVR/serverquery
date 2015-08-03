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

namespace SQ\Game;

require __DIR__ . '/inc/ValveBuffer.class.php';

/**
 * Base class for all Valve-based games
 *
 * @author Steve Guidetti
 */
class Valve extends \SQ\Gameserver {

    protected $defaultConfig = array(
        /**
         * @var bool Hide bots from player count and list
         */
        'hideBots' => true,
    );
    protected $port = 27015;

    /**
     * Steam application id
     *
     * @var int
     */
    protected $appId = -1;

    /**
     * Number of bots on the server
     *
     * @var int
     */
    protected $numBots = 0;

    public function getConnectLink() {
        return 'steam://connect/' . $this->getAddress();
    }

    protected function query($timeout) {
        $fp = @stream_socket_client('udp://' . $this->getAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new \Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        try {
            $this->makeInfoRequest($fp);
            try {
                $this->makePlayerRequest($fp);
            } catch(\Exception $e) {
                $this->setPlayerList(array());
            }
        } catch(\Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Request server info from the server
     * 
     * @param resource $fp Handle to an open socket
     * @throws \Exception
     */
    protected function makeInfoRequest($fp) {
        $req = pack('ccccca*', 0xFF, 0xFF, 0xFF, 0xFF, 0x54, "Source Engine Query\0");
        fwrite($fp, $req);

        $res = self::assembleResponse($fp);

        if($res->getByte() !== 0x49) {
            throw new \Exception('Invalid info response');
        }

        $res->getByte(); // protocol version
        $this->setName($res->getString());
        $this->setMapName($res->getString());
        $res->getString(); // game dir
        $res->getString(); // game name
        $this->appId = $res->getShort();
        $this->setPlayerCount($res->getByte());
        $this->setMaxPlayers($res->getByte());
        $this->numBots = $res->getByte();

        if($this->config['hideBots']) {
            $this->setMaxPlayers($this->getMaxPlayers() - $this->numBots);
        }
    }

    /**
     * Request a challenge number needed to make player request
     * 
     * @param resource $fp Handle to an open socket
     * @return int Challenge number
     * @throws \Exception
     */
    protected static function getChallengeNumber($fp) {
        $req = pack('cccccl', 0xFF, 0xFF, 0xFF, 0xFF, 0x55, -1);
        fwrite($fp, $req);

        $res = self::assembleResponse($fp);
        if($res->getByte() !== 0x41) {
            throw new \Exception('Bad challenge response');
        }
        return $res->getLong();
    }

    /**
     * Request the list of players on the server
     * 
     * @param resource $fp Handle to an open socket
     * @throws \Exception
     */
    protected function makePlayerRequest($fp) {
        $challenge = self::getChallengeNumber($fp);

        $req = pack('cccccl', 0xFF, 0xFF, 0xFF, 0xFF, 0x55, $challenge);
        fwrite($fp, $req);

        $res = self::assembleResponse($fp);

        if($res->getByte() !== 0x44) {
            throw new \Exception('Invalid player response');
        }

        $this->readPlayerResponse($res);
    }

    /**
     * Read the response to the player list request
     * 
     * @param \SQ\Game\ValveBuffer $res
     */
    protected function readPlayerResponse(ValveBuffer $res) {
        $playerCount = $res->getByte();
        $players = array();
        while(count($players) < $playerCount) {
            $player = array();

            $res->getByte(); // player index
            $player['name'] = $res->getString();
            $player['score'] = $res->getLong();
            $player['time'] = (int)$res->getFloat();
            if($this->appId === 2400) { // the ship
                $res->getLong(); // deaths
                $res->getLong(); // money
            }

            $players[] = $player;
        }

        $this->setPlayerList($this->filterPlayerList($players));
    }

    /**
     * Filter out blank player names and optionally bots
     * 
     * @param mixed[] $playerList
     * @return string[] List of player names
     */
    public function filterPlayerList(array $playerList) {
        if($this->config['hideBots'] && $this->numBots > 0) {
            $playerList = self::filterBots($playerList);
            $this->setPlayerCount(count($playerList));
        }

        $playerNames = array();
        foreach($playerList as $player) {
            if($player['name'] === '') {
                continue;
            }

            $playerNames[] = $player['name'];
        }

        return $playerNames;
    }

    /**
     * Removes bots from the player list
     * 
     * @param mixed[] $playerList
     * @return mixed[] Filtered list of players
     */
    protected static function filterBots(array $playerList) {
        $filteredList = array();
        $maxTime = 0;
        foreach($playerList as $player) {
            $maxTime = max(array($player['time'], $maxTime));
        }
        foreach($playerList as $player) {
            if($player['time'] !== $maxTime) {
                $filteredList[] = $player;
            }
        }

        return $filteredList;
    }

    /**
     * Read response and combine packets if necessary
     * 
     * @param resource $fp Handle to an open socket
     * @return \SQ\Game\ValveBuffer Response payload
     * @throws \Exception
     */
    protected static function assembleResponse($fp) {
        $buffer = new ValveBuffer();

        $buffer->set(fread($fp, 1400));

        if($buffer->remaining() < 4) {
            throw new \Exception('Invalid response from server');
        }

        if($buffer->getLong() == -1) {
            return $buffer;
        } else {
            throw new \Exception('Multipart responses are not yet implemented');
        }
    }

}
