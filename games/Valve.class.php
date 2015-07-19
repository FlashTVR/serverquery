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

require 'lib/ValveBuffer.class.php';

/**
 * Base class for all Valve-based games
 *
 * @author Steve Guidetti
 */
class Game_Valve extends Gameserver {

    protected $port = 27015;

    /**
     *
     * @var int Steam application id
     */
    protected $appId = -1;

    public function getConnectLink() {
        return 'steam://connect/' . $this->getAddress();
    }

    public function query() {
        $fp = @stream_socket_client('udp://' . $this->getAddress(), $errno, $errstr);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }
        
        stream_set_timeout($fp, 5);
        
        $this->makeInfoRequest($fp);
        $this->makePlayerRequest($fp);
    }

    /**
     * Request server info from the server
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function makeInfoRequest($fp) {
        $req = pack('ccccca*', 0xFF, 0xFF, 0xFF, 0xFF, 0x54, "Source Engine Query\0");
        fwrite($fp, $req);
        
        $res = self::assembleResponse($fp);
        
        if($res->getByte() !== 0x49) {
            throw new Exception('Invalid info response');
        }
        
        $res->getByte(); // protocol version
        $this->setName($res->getString());
        $this->setMapName($res->getString());
        $res->getString(); // game dir
        $res->getString(); // game name
        $this->appId = $res->getShort();
        $this->setPlayerCount($res->getByte());
        $this->setMaxPlayers($res->getByte());
    }

    /**
     * Request the list of players on the server
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function makePlayerRequest($fp) {
        $challenge = self::getChallengeNumber($fp);
        
        $req = pack('cccccl', 0xFF, 0xFF, 0xFF, 0xFF, 0x55, $challenge);
        fwrite($fp, $req);
        
        $res = self::assembleResponse($fp);
        
        if($res->getByte() !== 0x44) {
            throw new Exception('Invalid player response');
        }
        
        $playerCount = $res->getByte();
        $players = array();
        while(count($players) < $playerCount) {
            $res->getByte(); // player index
            $players[] = $res->getString();
            $res->getLong(); // score
            $res->getFloat(); // duration
            if($this->appId === 2400) { // the ship
                $res->getLong(); // deaths
                $res->getLong(); // money
            }
        }
        $this->setPlayerList($players);
    }

    /**
     * Request a challenge number needed to make player request
     * 
     * @param resource $fp Handle to an open socket
     * @return int Challenge number
     * @throws Exception
     */
    protected static function getChallengeNumber($fp) {
        $req = pack('cccccl', 0xFF, 0xFF, 0xFF, 0xFF, 0x55, -1);
        fwrite($fp, $req);
        
        $res = self::assembleResponse($fp);
        if($res->getByte() !== 0x41) {
            throw new Exception('Bad challenge response');
        }
        return $res->getLong();
    }

    /**
     * Read response and combine packets if necessary
     * 
     * @param resource $fp Handle to an open socket
     * @return ValveBuffer Response payload
     * @throws Exception
     */
    protected static function assembleResponse($fp) {
        $buffer = new ValveBuffer();
        
        $buffer->set(fread($fp, 1400));
        
        if($buffer->remaining() < 4) {
            throw new Exception('Invalid response from server');
        }
        
        if($buffer->getLong() == -1) {
            return $buffer;
        } else {
            throw new Exception('Multipart responses are not yet implemented');
        }
    }
}
