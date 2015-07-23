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
 * Application configuration
 *
 * @author Steve Guidetti
 */
class SQConfig {

    /**
     * Enable caching of server response data
     */
    const CACHE_ENABLE = true;

    /**
     * Time in seconds before cached items are considered stale
     */
    const CACHE_TIME = 5;

    /**
     * URL path to the directory containing serverquery.css and img/
     */
    const WEB_PATH = 'serverquery/';

    /**
     * Game configurations
     *
     * Format:
     * array[gameId]['name'] string Name of the game
     * array[gameId]['class'] string Name of the query class used by this game
     * array[gameId]['config'] mixed[] Optional default configuration for this game
     *
     * @var mixed[]
     */
    public static $games = array(
        'tf2' => array(
            'name'  => 'Team Fortress 2',
            'class' => 'Game_Valve',
        ),
        'dod' => array(
            'name'  => 'Day of Defeat',
            'class' => 'Game_Valve',
        ),
        'minecraft' => array(
            'name'  => 'Minecraft',
            'class' => 'Game_Minecraft',
        ),
        'tekkit' => array(
            'name'  => 'Tekkit',
            'class' => 'Game_Minecraft',
            'config'    => array(
                'useLegacy' => true,
            ),
        ),
        'terraria' => array(
            'name'  => 'Terraria',
            'class' => 'Game_TShock',
        ),
    );

    /**
     * Server configurations
     *
     * Format:
     * array[]['game'] string A key from $games
     * array[]['addr'] string Hostname for the server
     * array[]['config'] mixed[] Optional configuration for this server
     *
     * @var mixed[]
     */
    public static $servers = array(
        array(
            'game' => 'tf2',
            'addr' => '127.0.0.1:27015',
        ),
    );
}
