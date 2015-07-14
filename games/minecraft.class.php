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
 * Game: Minecraft
 *
 * @author Steve Guidetti
 */
class Game_Minecraft extends Gameserver {

    protected $port = 25565;

    public function getGameName() {
        return 'Minecraft';
    }

    public function query() {
        $fp = stream_socket_client('udp://' . $this->getAddress(), $errno, $errstr);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, 5);

        $sessId = rand() & 0x0F0F0F0F;

        $token = $this->performHandshake($fp, $sessId);
        $this->requestStat($fp, $sessId, $token);
        fread($fp, 11);
        $this->parseKeyValues($fp);
        fread($fp, 10);
        $this->parsePlayers($fp);
    }

    /**
     * Perform handshake to receive challenge token
     *
     * @param resource $fp Handle to an open socket
     * @param int $sessId Session ID (random number)
     * @return int Token
     */
    private function performHandshake($fp, $sessId) {
        $req = pack('cccN', 0xFE, 0xFD, 9, $sessId);
        fwrite($fp, $req);

        $res = fread($fp, 2048);
        if(strlen($res) < 5) {
            throw new Exception('Invalid handshake response');
        }
        $header = unpack('ctype/NsessId', $res);
        if($header['type'] !== 9 || $header['sessId'] !== $sessId) {
            throw new Exception('Invalid handshake header');
        }
        return (int)substr($res, 5, -1);
    }

    /**
     * Make request for full server stat
     *
     * @param resource $fp Handle to an open socket
     * @param int $sessId Session ID
     * @param int $token Challenge token
     */
    private function requestStat($fp, $sessId, $token) {
        $req = pack('cccNNN', 0xFE, 0xFD, 0, $sessId, $token, 0);
        fwrite($fp, $req);

        $res = fread($fp, 5);
        $header = unpack('ctype/NsessId', $res);
        if($header['type'] !== 0 || $header['sessId'] !== $sessId) {
            throw new Exception('Invalid response header');
        }
    }

    /**
     * Parse the key value section of the response
     *
     * @param resource $fp Handle to an open socket
     */
    private function parseKeyValues($fp) {
        $info = array();
        $key = $val = '';
        $keyRead = false;
        while(true) {
            $res = fread($fp, 1);
            if(!$keyRead) {
                if($res === "\0" || $res === false) {
                    if(strlen($key) === 0) {
                        break;
                    } else {
                        $keyRead = true;
                    }
                } else {
                    $key .= $res;
                }
            } else {
                if($res === "\0" || $res === false) {
                    $info[$key] = $val;
                    $key = $val = '';
                    $keyRead = false;
                } else {
                    $val .= $res;
                }
            }
        }

        $this->setServerInfo($info);
    }

    /**
     * Set relevant properties from the key values
     *
     * @param array $info Associative array of server properties
     */
    private function setServerInfo(array $info) {
        $this->setName($info['hostname']);
        $this->setPlayerCount((int)$info['numplayers']);
        $this->setMaxPlayers((int)$info['maxplayers']);
    }

    /**
     * Parse the players section of the response
     *
     * @param resource $fp Handle to an open socket
     */
    private function parsePlayers($fp) {
        $players = array();
        $val = '';
        while(true) {
            $res = fread($fp, 1);
            if($res === "\0" || $res === false) {
                if(strlen($val) === 0) {
                    break;
                } else {
                    $players[] = $val;
                    $val = '';
                }
            } else {
                $val .= $res;
            }
        }

        $this->setPlayerList($players);
    }
}
