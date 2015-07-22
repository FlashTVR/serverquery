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
     * Miscellaneous configuration options
     *
     * @var mixed[]
     */
    protected $config = array();

    /**
     * Default configuration
     *
     * @var mixed[]
     */
    protected $defaultConfig = array();

    /**
     * Server hostname
     *
     * @var string
     */
    private $hostname;

    /**
     * Server port
     *
     * @var int
     */
    protected $port = 0;

    /**
     * Server name
     *
     * @var string|null
     */
    private $name = null;

    /**
     * Name of current map
     *
     * @var string|null
     */
    private $mapName = null;

    /**
     * Number of players connected
     *
     * @var int
     */
    private $playerCount = 0;

    /**
     * Maximum number of players allowed
     *
     * @var int
     */
    private $maxPlayers = 0;

    /**
     * List of connected players
     *
     * @var string[]|null
     */
    private $playerList = null;

    /**
     * Unix timestamp of the last update
     *
     * @var int
     */
    private $queryTime = 0;

    /**
     * Error message thrown from the query method
     *
     * @var string|null
     */
    private $error = null;

    /**
     * Server responded to the query
     *
     * @var bool
     */
    private $online = false;

    /**
     * Constructor
     * 
     * @param string $addr Full server address
     * @param mixed[] $config Optional array of options for this instance
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
    public function setAddress($addr) {
        if(strpos(':', $addr) !== false) {
            $parts = explode(':', $addr);
            $this->hostname = $parts[0];
            $this->port = (int)$parts[1];
        } else {
            $this->hostname = $addr;
        }
    }

    /**
     * Get the current configuration for this instance
     * 
     * @return mixed[]
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Set the configuration for this instance
     * 
     * @param mixed[] $config
     */
    public function setConfig(array $config) {
        $this->config = array_merge($this->defaultConfig, $config);
    }

    /**
     * Get the server hostname
     * 
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * Get the server game port
     * 
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Get link to connect directly to the server
     * 
     * @return string|null NULL if unsupported
     */
    public function getConnectLink() {
        return null;
    }

    /**
     * Get server name
     * 
     * @return string|null NULL if unsupported
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set server name
     * 
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name of map currently running
     * 
     * @return string|null NULL if unsupported
     */
    public function getMapName() {
        return $this->mapName;
    }

    /**
     * Set name of current map
     * 
     * @param string $mapName
     */
    public function setMapName($mapName) {
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
    public function setPlayerCount($count) {
        $this->playerCount = (int)$count;
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
    public function setMaxPlayers($maxPlayers) {
        $this->maxPlayers = (int)$maxPlayers;
    }

    /**
     * Get list of players connected to the server
     * 
     * @return string[]|null NULL if unsupported
     */
    public function getPlayerList() {
        return $this->playerList;
    }

    /**
     * Set current list of players
     * 
     * @param string[] $playerList
     */
    public function setPlayerList(array $playerList) {
        $this->playerList = $playerList;
    }

    /**
     * The server is online
     * 
     * @return bool
     */
    public final function isOnline() {
        return $this->online;
    }

    /**
     * Unix timestamp of the last update
     * 
     * @return int
     */
    public final function getQueryTime() {
        return $this->queryTime;
    }

    /**
     * Get the last query error
     * 
     * @return string|null
     */
    public final function getError() {
        return $this->error;
    }

    /**
     * Update the server details
     */
    public final function update() {
        $this->error = null;
        $this->online = false;

        try {
            $this->query();
            $this->online = true;
        } catch(Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->queryTime = time();
    }

    /**
     * Query the server for stats over the network
     * 
     * @throws Exception
     */
    protected abstract function query();
}
