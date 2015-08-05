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

namespace SQ;

/**
 * Holds data about a server to use in the template
 *
 * @author Steve Guidetti
 */
class TemplateHolder {

    /**
     * This server is online
     *
     * @var bool
     */
    public $online;

    /**
     * The error returned by the query (if any)
     *
     * @var string
     */
    public $error;

    /**
     * The key for this server's game configuration
     *
     * @var string
     */
    public $gameId;

    /**
     * The full name of the game running on this server
     *
     * @var string
     */
    public $gameName;

    /**
     * The path to the icon image for the game
     *
     * @var string
     */
    public $gameIcon;

    /**
     * The full address to this server
     *
     * @var string
     */
    public $addr;

    /**
     * The link to connect to the server (if supported)
     *
     * @var string
     */
    public $link;

    /**
     * The name of this server
     *
     * @var string
     */
    public $name;

    /**
     * The name of the map running on this server (if supported)
     *
     * @var string
     */
    public $map;

    /**
     * The number of players currently connected to this server
     *
     * @var int
     */
    public $playerCount;

    /**
     * The maximum number of players this server allows
     *
     * @var int
     */
    public $maxPlayers;

    /**
     * List of active players on this server (if supported)
     *
     * @var string[]
     */
    public $players;

}
