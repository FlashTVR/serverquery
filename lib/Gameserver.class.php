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
abstract class Gameserver {

    /**
     *
     * @var array Miscellaneous configuration options
     */
    protected $config = array();

    /**
     *
     * @var string Server hostname
     */
    protected $hostname = null;

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
     * @var string Name of current map
     */
    protected $mapName = null;

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
    protected $playerList = null;

    /**
     * Constructor
     * 
     * @param string $addr Full server address
     * @param array $config Optional array of options for this instance
     */
    public function __construct($addr, array $config = array()) {
        $this->setAddress($addr);
        $this->setConfig($config);
    }

    /**
     * Get full server address
     * 
     * @return string
     */
    public function getAddress() {
        return $this->hostname . ':' . $this->port;
    }

    /**
     * Set full server address
     * 
     * @param string $addr Format: "hostname" or "hostname:port"
     */
    protected function setAddress($addr) {
        if(strpos(':', $addr) !== false) {
            $parts = explode(':', $addr);
            $this->hostname = $parts[0];
            $this->port = (int) $parts[1];
        } else {
            $this->hostname = $addr;
        }
    }

    /**
     * Set the configuration for this instance
     * 
     * @param array $config
     */
    protected function setConfig(array $config) {
        $this->config = $config;
    }

    /**
     * Get link to connect directly to the server
     * 
     * @return string NULL if unsupported
     */
    public function getConnectLink() {
        return null;
    }

    /**
     * Get server name
     * 
     * @return string NULL if unsupported
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set server name
     * 
     * @param string $name
     */
    protected function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name of map currently running
     * 
     * @return string NULL if unsupported
     */
    public function getMapName() {
        return $this->mapName;
    }

    /**
     * Set name of current map
     * 
     * @param string $mapName
     */
    protected function setMapName($mapName) {
        $this->mapName = $mapName;
    }

    /**
     * Get number of players connected to the server
     * 
     * @return int
     */
    public function getPlayerCount() {
        return $this->playerCount;
    }

    /**
     * Set current player count
     * 
     * @param int $count
     */
    protected function setPlayerCount($count) {
        if(!is_int($count)) {
            throw new InvalidArgumentException;
        }
        $this->playerCount = $count;
    }

    /**
     * Get maximum number of players allowed on the server
     * 
     * @return int
     */
    public function getMaxPlayers() {
        return $this->maxPlayers;
    }

    /**
     * Set maximum number of players allowed
     * 
     * @param int $maxPlayers
     */
    protected function setMaxPlayers($maxPlayers) {
        if(!is_int($maxPlayers)) {
            throw new InvalidArgumentException;
        }
        $this->maxPlayers = $maxPlayers;
    }

    /**
     * Get list of players connected to the server
     * 
     * @return array NULL if unsupported
     */
    public function getPlayerList() {
        return $this->playerList;
    }

    /**
     * Set current list of players
     * 
     * @param array $playerList
     */
    protected function setPlayerList(array $playerList) {
        $this->playerList = $playerList;
    }

    /**
     * Query the server for stats over the network
     */
    public abstract function query();
}
