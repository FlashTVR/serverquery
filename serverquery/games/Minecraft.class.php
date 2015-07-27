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

    protected $defaultConfig = array(
        'useQuery' => false,
        'queryPort' => 25565,
        'useLegacy' => false,
    );
    protected $port = 25565;

    public function setName($name) {
        // strip color and formatting codes
        $name = preg_replace('/\xA7./', '', $name);
        parent::setName($name);
    }

    protected function query($timeout) {
        if($this->config['useLegacy']) {
            $this->queryLagacy($timeout);
        } elseif($this->config['useQuery']) {
            $this->queryQuery($timeout);
        } else {
            $this->querySLP($timeout);
        }
    }

    /**
     * Query the server using the Server List Ping protocol
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws Exception
     */
    protected function querySLP($timeout) {
        $fp = @stream_socket_client('tcp://' . $this->getAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        try {
            $this->sendSLPRequest($fp);
            $this->readSLPResponse($fp);
        } catch(Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Send a Server List Ping request
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function sendSLPRequest($fp) {
        $req = array(
            chr(0),
            self::packVarInt(47),
            self::packSLPString($this->getHostname()),
            pack('n', $this->getPort()),
            self::packVarInt(1)
        );
        $req = self::packSLPString(implode('', $req));
        fwrite($fp, $req);

        $req = self::packSLPString(chr(0));
        fwrite($fp, $req);
    }

    /**
     * Read the JSON text from the Server List Ping response
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function readSLPResponse($fp) {
        self::unpackVarInt($fp);
        self::unpackVarInt($fp);
        $length = self::unpackVarInt($fp);

        $json = '';
        while(strlen($json) < $length) {
            $json .= fread($fp, 2048);
        }

        $this->parseSLPJson($json);
    }

    /**
     * Parse a Server List Ping JSON string and set server status properties
     * 
     * @param string $jsonString
     * @throws Exception
     */
    protected function parseSLPJson($jsonString) {
        $json = json_decode($jsonString);
        if(!$json) {
            throw new Exception('Invalid JSON string');
        }

        $this->setName($json->description);
        $this->setPlayerCount($json->players->online);
        $this->setMaxPlayers($json->players->max);

        if(property_exists($json->players, 'sample')) {
            $players = array();
            foreach($json->players->sample as $player) {
                $players[] = $player->name;
            }
            $this->setPlayerList($players);
        }
    }

    /**
     * Pack a string into protocol String format
     * 
     * @param string $string
     * @return string Length of string in VarInt followed by the string
     */
    protected static function packSLPString($string) {
        return self::packVarInt(strlen($string)) . $string;
    }

    /**
     * Pack integer into protocol VarInt format
     * 
     * @param int $int
     * @return string
     */
    protected static function packVarInt($int) {
        $varInt = '';
        while(true) {
            if(($int & 0xFFFFFF80) === 0) {
                $varInt .= chr($int);
                return $varInt;
            }
            $varInt .= chr($int & 0x7F | 0x80);
            $int >>= 7;
        }
    }

    /**
     * Read and unpack protocol VarInt into integer
     * 
     * @param resource $fp Handle to an open socket
     * @return int
     * @throws Exception
     */
    protected static function unpackVarInt($fp) {
        $int = 0;
        $pos = 0;
        while(true) {
            $byte = ord(fread($fp, 1));
            $int |= ($byte & 0x7F) << $pos++ * 7;
            if($pos > 5) {
                throw new Exception('VarInt too large');
            }
            if(($byte & 0x80) !== 128) {
                return $int;
            }
        }
    }

    /**
     * Query the server using the Query protocol
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws Exception
     */
    protected function queryQuery($timeout) {
        $fp = @stream_socket_client('udp://' . $this->getQueryAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        $sessId = rand() & 0x0F0F0F0F;

        try {
            $token = $this->performQueryHandshake($fp, $sessId);
            $this->requestQueryStat($fp, $sessId, $token);
            fread($fp, 11);
            $this->parseQueryKeyValues($fp);
            fread($fp, 10);
            $this->parseQueryPlayers($fp);
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
    protected function getQueryAddress() {
        return $this->getHostname() . ':' . $this->config['queryPort'];
    }

    /**
     * Perform handshake to receive challenge token
     *
     * @param resource $fp Handle to an open socket
     * @param int $sessId Session ID (random number)
     * @return int Token
     * @throws Exception
     */
    private function performQueryHandshake($fp, $sessId) {
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
     * Make request for full server stat using the Query protocol
     *
     * @param resource $fp Handle to an open socket
     * @param int $sessId Session ID
     * @param int $token Challenge token
     * @throws Exception
     */
    private function requestQueryStat($fp, $sessId, $token) {
        $req = pack('cccNNN', 0xFE, 0xFD, 0, $sessId, $token, 0);
        fwrite($fp, $req);

        $res = fread($fp, 5);
        $header = unpack('ctype/NsessId', $res);
        if($header['type'] !== 0 || $header['sessId'] !== $sessId) {
            throw new Exception('Invalid response header');
        }
    }

    /**
     * Parse the key value section of the Query response
     *
     * @param resource $fp Handle to an open socket
     */
    private function parseQueryKeyValues($fp) {
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

        $this->setQueryServerInfo($info);
    }

    /**
     * Set relevant properties from the Query key values
     *
     * @param string[] $info Associative array of server properties
     */
    private function setQueryServerInfo(array $info) {
        $this->setName($info['hostname']);
        $this->setMapName($info['map']);
        $this->setPlayerCount((int)$info['numplayers']);
        $this->setMaxPlayers((int)$info['maxplayers']);
    }

    /**
     * Parse the players section of the Query response
     *
     * @param resource $fp Handle to an open socket
     */
    private function parseQueryPlayers($fp) {
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

    /**
     * Query the server using the old Server List Ping request
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws Exception
     */
    protected function queryLagacy($timeout) {
        $fp = @stream_socket_client('tcp://' . $this->getAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        try {
            $this->makeLegacyRequest($fp);
            $this->readLegacyResponse($fp);
        } catch(Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Pack a string into legacy Server List Ping packet format
     * 
     * @param string $string
     * @return string
     */
    protected static function packUTF16BEString($string) {
        $len = strlen($string);
        return pack('n', $len) . mb_convert_encoding($string, 'UTF-16BE');
    }

    /**
     * Decode a UTF-16BE string from a legacy Server List Ping response
     * 
     * @param string $string UTF-16BE string
     * @return string UTF-8 string
     */
    protected static function decodeUTF16BEString($string) {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-16BE');
    }

    /**
     * Send a legacy Server List Ping request to the server
     * 
     * @param resource $fp Handle to an open socket
     */
    protected function makeLegacyRequest($fp) {
        $req = pack('nc', 0xFE01, 0xFA);
        $req .= self::packUTF16BEString('MC|PingHost');
        $req .= pack('nc', 7 + 2 * strlen($this->getHostname()), 73);
        $req .= self::packUTF16BEString($this->getHostname());
        $req .= pack('N', $this->getPort());

        fwrite($fp, $req);
    }

    /**
     * Read and parse the response from the legacy Server List Ping request
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function readLegacyResponse($fp) {
        $res = fread($fp, 3);
        if(strpos($res, 0xFF) !== 0) {
            throw new Exception('Invalid ping response');
        }

        $res = fread($fp, 2048);
        $data = explode(pack('n', 0), $res);

        $this->setName(self::decodeUTF16BEString($data[3]));
        $this->setPlayerCount((int)self::decodeUTF16BEString($data[4]));
        $this->setMaxPlayers((int)self::decodeUTF16BEString($data[5]));
    }

}
