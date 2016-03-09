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

namespace SQ\Game;

/**
 * Uses the post-1.7 Server List Ping protocol to query Minecraft servers
 *
 * @author Steve Guidetti
 */
class MinecraftSLP {

    /**
     * Mincraft Gameserver object
     *
     * @var \SQ\Game\Minecraft
     */
    private $gs;

    /**
     * Constructor
     * 
     * @param \SQ\Game\Minecraft $gs Mincraft Gameserver object
     */
    public function __construct(Minecraft $gs) {
        $this->gs = $gs;
    }

    /**
     * Query the server using the Server List Ping protocol
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws \Exception
     */
    public function query($timeout) {
        $fp = @stream_socket_client('tcp://' . $this->gs->getAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new \Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        try {
            $this->sendRequest($fp);
            $this->readResponse($fp);
        } catch(\Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Send a Server List Ping request
     * 
     * @param resource $fp Handle to an open socket
     * @throws \Exception
     */
    private function sendRequest($fp) {
        $req = array(
            chr(0),
            self::packVarInt(47),
            self::packString($this->gs->getHostname()),
            pack('n', $this->gs->getPort()),
            self::packVarInt(1)
        );
        $req = self::packString(implode('', $req));
        fwrite($fp, $req);

        $req = self::packString(chr(0));
        fwrite($fp, $req);
    }

    /**
     * Read the JSON text from the response
     * 
     * @param resource $fp Handle to an open socket
     * @throws \Exception
     */
    private function readResponse($fp) {
        self::unpackVarInt($fp);
        self::unpackVarInt($fp);
        $length = self::unpackVarInt($fp);

        $json = '';
        while(strlen($json) < $length) {
            $json .= fread($fp, 2048);
        }

        $this->parseJSON($json);
    }

    /**
     * Parse the JSON string and set server status properties
     * 
     * @param string $jsonString
     * @throws \Exception
     */
    private function parseJSON($jsonString) {
        $json = json_decode($jsonString);
        if(!$json) {
            throw new \Exception('Invalid JSON string');
        }

        $name = $json->description;
        $this->gs->setName(is_string($name) ? $name : $name->text);
        $this->gs->setPlayerCount($json->players->online);
        $this->gs->setMaxPlayers($json->players->max);

        if(property_exists($json->players, 'sample')) {
            $players = array();
            foreach($json->players->sample as $player) {
                $players[] = $player->name;
            }
            $this->gs->setPlayerList($players);
        }
    }

    /**
     * Pack a string into protocol String format
     * 
     * @param string $string
     * @return string Length of string in VarInt followed by the string
     */
    private static function packString($string) {
        return self::packVarInt(strlen($string)) . $string;
    }

    /**
     * Pack integer into protocol VarInt format
     * 
     * @param int $int
     * @return string
     */
    private static function packVarInt($int) {
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
     * @throws \Exception
     */
    private static function unpackVarInt($fp) {
        $int = 0;
        $pos = 0;
        while(true) {
            $byte = ord(fread($fp, 1));
            $int |= ($byte & 0x7F) << $pos++ * 7;
            if($pos > 5) {
                throw new \Exception('VarInt too large');
            }
            if(($byte & 0x80) !== 128) {
                return $int;
            }
        }
    }

}
