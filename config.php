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

class SQConfig {
    public static $games = array(
        'tf2' => array(
            'name' => 'Team Fortress 2',
            'class' => 'Game_Source'
        ),
        'dod' => array(
            'name' => 'Day of Defeat',
            'class' => 'Game_Goldsource'
        ),
        'minecraft' => array(
            'name' => 'Minecraft',
            'class' => 'Game_MinecraftQuery'
        ),
        'minecraft_old' => array(
            'name' => 'Minecraft',
            'class' => 'Game_Minecraft'
        ),
        'tekkit' => array(
            'name' => 'Tekkit',
            'class' => 'Game_Minecraft'
        ),
    );
    
    public static $servers = array(
        array(
            'game' => 'minecraft',
            'addr' => '127.0.0.1'
        ),
    );
}
