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
 * Game: Tekkit (Minecraft mod)
 *
 * @author Steve Guidetti
 */
class Game_Tekkit extends Gameserver {

    protected $port = 25565;

    /**
     * Pack a string into packet format
     * 
     * @param string $string
     * @return string
     */
    protected static function packString($string) {
        $len = strlen($string);
        return pack('n', $len) . mb_convert_encoding($string, 'UTF-16BE');
    }

    /**
     * Decode a UTF-16BE string
     * 
     * @param string $string UTF-16BE string
     * @return string UTF-8 string
     */
    protected static function decodeString($string) {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-16BE');
    }

    public function query() {
        $fp = stream_socket_client('tcp://' . $this->getAddress(), $errno, $errstr);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, 5);

        $this->makeRequest($fp);
        $this->readResponse($fp);
    }

    /**
     * Send a Server List Ping request to the server
     * 
     * @param resource $fp Handle to an open socket
     */
    protected function makeRequest($fp) {
        $req = pack('nc', 0xFE01, 0xFA);
        $req .= self::packString('MC|PingHost');
        $req .= pack('nc', 7 + 2 * strlen($this->hostname), 73);
        $req .= self::packString($this->hostname);
        $req .= pack('N', $this->port);

        fwrite($fp, $req);
    }

    /**
     * Read and parse the response from the server
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    protected function readResponse($fp) {
        $res = fread($fp, 3);
        if(strpos($res, 0xFF) !== 0) {
            throw new Exception('Invalid ping response');
        }

        $res = fread($fp, 2048);
        $data = explode(pack('n', 0), $res);

        $this->setName(self::decodeString($data[3]));
        $this->setPlayerCount((int)self::decodeString($data[4]));
        $this->setMaxPlayers((int)self::decodeString($data[5]));
    }
}
