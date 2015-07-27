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
 * Description of MinecraftQuery
 *
 * @author Steve Guidetti
 */
class MinecraftQuery {

    /**
     * Mincraft Gameserver object
     *
     * @var Game_Minecraft
     */
    private $gs;

    /**
     * Port used to query the server
     *
     * @var int
     */
    private $queryPort;

    /**
     * Constructor
     * 
     * @param Game_Minecraft $gs Mincraft Gameserver object
     * @param int $queryPort Port used to query the server
     */
    public function __construct(Game_Minecraft $gs, $queryPort = 25565) {
        $this->gs = $gs;
        $this->queryPort = $queryPort;
    }

    /**
     * Query the server using the Query protocol
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws Exception
     */
    public function query($timeout) {
        $fp = @stream_socket_client('udp://' . $this->getQueryAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        $sessId = rand() & 0x0F0F0F0F;

        try {
            $token = self::performHandshake($fp, $sessId);
            self::requestStat($fp, $sessId, $token);
            fread($fp, 11);
            $this->parseKeyValues($fp);
            fread($fp, 10);
            $this->parsePlayers($fp);
        } catch(Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Get the address used to query the server
     * 
     * @return string
     */
    private function getQueryAddress() {
        return $this->gs->getHostname() . ':' . $this->queryPort;
    }

    /**
     * Perform handshake to receive challenge token
     *
     * @param resource $fp Handle to an open socket
     * @param int $sessId Session ID (random number)
     * @return int Token
     * @throws Exception
     */
    private static function performHandshake($fp, $sessId) {
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
     * @throws Exception
     */
    private static function requestStat($fp, $sessId, $token) {
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
     * @param string[] $info Associative array of server properties
     */
    private function setServerInfo(array $info) {
        $this->gs->setName($info['hostname']);
        $this->gs->setMapName($info['map']);
        $this->gs->setPlayerCount((int)$info['numplayers']);
        $this->gs->setMaxPlayers((int)$info['maxplayers']);
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

        $this->gs->setPlayerList($players);
    }

}
