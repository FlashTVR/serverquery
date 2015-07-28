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
 * Description of MinecraftLegacy
 *
 * @author Steve Guidetti
 */
class SQ_MinecraftLegacy {

    /**
     * Mincraft Gameserver object
     *
     * @var SQ_Game_Minecraft
     */
    private $gs;

    /**
     * Constructor
     * 
     * @param SQ_Game_Minecraft $gs Mincraft Gameserver object
     */
    public function __construct(SQ_Game_Minecraft $gs) {
        $this->gs = $gs;
    }

    /**
     * Query the server using the old Server List Ping request
     * 
     * @param int $timeout Socket timeout in seconds
     * @throws Exception
     */
    public function query($timeout) {
        $fp = @stream_socket_client('tcp://' . $this->gs->getAddress(), $errno, $errstr, $timeout);
        if(!$fp) {
            throw new Exception($errstr, $errno);
        }

        stream_set_timeout($fp, $timeout);

        try {
            $this->sendRequest($fp);
            $this->readResponse($fp);
        } catch(Exception $e) {
            fclose($fp);
            throw $e;
        }

        fclose($fp);
    }

    /**
     * Send a Server List Ping request to the server
     * 
     * @param resource $fp Handle to an open socket
     */
    private function sendRequest($fp) {
        $req = pack('nc', 0xFE01, 0xFA);
        $req .= self::packUTF16BEString('MC|PingHost');
        $req .= pack('nc', 7 + 2 * strlen($this->gs->getHostname()), 73);
        $req .= self::packUTF16BEString($this->gs->getHostname());
        $req .= pack('N', $this->gs->getPort());

        fwrite($fp, $req);
    }

    /**
     * Read and parse the response
     * 
     * @param resource $fp Handle to an open socket
     * @throws Exception
     */
    private function readResponse($fp) {
        $res = fread($fp, 3);
        if(strpos($res, 0xFF) !== 0) {
            throw new Exception('Invalid ping response');
        }

        $res = fread($fp, 2048);
        $data = explode(pack('n', 0), $res);

        $this->gs->setName(self::decodeUTF16BEString($data[3]));
        $this->gs->setPlayerCount((int)self::decodeUTF16BEString($data[4]));
        $this->gs->setMaxPlayers((int)self::decodeUTF16BEString($data[5]));
    }

    /**
     * Pack a string into legacy packet format
     * 
     * @param string $string
     * @return string
     */
    private static function packUTF16BEString($string) {
        $len = strlen($string);
        return pack('n', $len) . mb_convert_encoding($string, 'UTF-16BE');
    }

    /**
     * Decode a UTF-16BE string from a legacy response packet
     * 
     * @param string $string UTF-16BE string
     * @return string UTF-8 string
     */
    private static function decodeUTF16BEString($string) {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-16BE');
    }

}
