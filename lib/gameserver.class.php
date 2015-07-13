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
 * Base class for game query classes
 *
 * @author Steve Guidetti
 */
abstract class Gameserver implements IGameserver {
    
    /**
     *
     * @var string Server IP address
     */
    protected $ip = null;
    
    /**
     *
     * @var int Server port
     */
    protected $port = 0;
    
    /**
     *
     * @var string Server name
     */
    protected $name = null;
    
    /**
     *
     * @var int Number of players connected
     */
    protected $playerCount = 0;
    
    /**
     *
     * @var int Maximum number of players allowed
     */
    protected $maxPlayers = 0;
    
    /**
     *
     * @var array List of connected players
     */
    protected $playerList = array();
    
    public function __construct($addr) {
        $this->setAddress($addr);
    }
    
    protected function setAddress($addr) {
        if(strpos(':', $addr) !== false) {
            $parts = explode(':', $addr);
            $this->ip = $parts[0];
            $this->port = (int)$parts[1];
        } else {
            $this->ip = $addr;
        }
    }

    public function getAddress() {
        return $this->ip . ':' . $this->port;
    }

    public function getMaxPlayers() {
        return $this->maxPlayers;
    }

    public function getName() {
        return $this->name;
    }

    public function getPlayerCount() {
        return $this->playerCount;
    }

    public function getPlayerList() {
        return $this->playerList;
    }
    
    public function getMapName() {
        return null;
    }
    
    public function getConnectLink() {
        return null;
    }
}
