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
 * Interface for all game query classes
 *
 * @author Steve Guidetti
 */
interface IGameserver {
    
    /**
     * Constructor
     * 
     * @param string $addr Server IP address
     */
    public function __construct($addr);

    /**
     * Get server name
     * 
     * @return string NULL if unsupported
     */
    public function getName();
    
    /**
     * Get server IP address
     * 
     * @return string
     */
    public function getAddress();
    
    /**
     * Get number of players connected to the server
     * 
     * @return int
     */
    public function getPlayerCount();
    
    /**
     * Get maximum number of players allowed on the server
     * 
     * @return int
     */
    public function getMaxPlayers();
    
    /**
     * Get list of players connected to the server
     * 
     * @return array NULL if unsupported
     */
    public function getPlayerList();
    
    /**
     * Get name of map currently running
     * 
     * @return string NULL if unsupported
     */
    public function getMapName();
    
    /**
     * Get link to connect directly to the server
     * 
     * @return string NULL if unsupported
     */
    public function getConnectLink();
    
    /**
     * Get the full name of the game
     * 
     * @return string
     */
    public function getGameName();
}
