<?php

/*
 * The MIT License
 *
 * Copyright 2018 Steve Guidetti.
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
 * Game: TeamSpeak 3
 *
 * @author Steve Guidetti
 */
class TeamSpeak3 extends \SQ\Gameserver {

    protected static $chars = array('\\', '/', ' ', '|', "\a", "\b", "\f", "\n", "\r", "\t", "\v");
    protected static $replace = array('\\\\', '\\/', '\\s', '\\p', '\\a', '\\b', '\\f', '\\n', '\\r', '\\t', '\\v');

    protected $defaultConfig = array(
        /**
         * @var int Port used by the ServerQuery protocol
         */
        'queryPort' => 10011,
        /**
         * @var string Username for the ServerQuery protocol
         */
        'queryUser' => '',
        /**
         * @var string Password for the ServerQuery protocol
         */
        'queryPass' => '',
    );
    protected $port = 9987;

    /**
     * @var resource The handle for the ServerQuery connection
     */
    protected $fp;

    /**
     * @var int The network timeout in seconds
     */
    protected $timeout;

    public function __destruct()
    {
        if ($this->fp)
        {
            $this->sendCommand('quit');
            fclose($this->fp);
        }
    }

    public function getConnectLink() {
        return 'ts3server://' . $this->getHostname() . '/?port=' . $this->getPort();
    }

    protected function query($timeout) {
        $this->timeout = $timeout;
        $this->fp = @stream_socket_client('tcp://' . $this->getHostname() . ':' . $this->config['queryPort'], $errno, $errstr, $timeout);
        if(!$this->fp) {
            throw new \Exception($errstr, $errno);
        }

        stream_set_blocking($this->fp, false);

        $sockets = array($this->fp);
        if (!stream_select($sockets, $write, $except, $timeout))
        {
            throw new \Exception('Connection timed out');
        }
        stream_get_contents($this->fp);

        $this->login();
        $this->makeInfoRequest();
        $this->makeClientRequest();
    }

    /**
     * Login and select the virtual server running on the specified port.
     *
     * @throws \Exception
     */
    protected function login() {
        if (!empty($this->config['queryUser']))
        {
            $user = self::escape($this->config['queryUser']);
            $pass = self::escape($this->config['queryPass']);
            $response = $this->sendCommand(sprintf('login client_login_name=%s client_login_password=%s', $user, $pass), $err, $msg);
            if ($response === null)
            {
                throw new \Exception('Invalid response from server');
            }
            if ($err !== 0) {
                throw new \Exception('Login failed: ' . $msg);
            }
        }

        $response = $this->sendCommand('use port=' . $this->getPort(), $err, $msg);
        if ($response === null)
        {
            throw new \Exception('Invalid response from server');
        }
        if ($err !== 0) {
            throw new \Exception('Server select failed: ' . $msg);
        }
    }

    /**
     * Request server info from the server
     *
     * @throws \Exception
     */
    protected function makeInfoRequest() {
        $response = $this->sendCommand('serverinfo', $err, $msg);
        if ($err !== 0) {
            throw new \Exception('Server info request failed: ' . $msg);
        }

        $this->setName($response[0]['virtualserver_name']);
        $this->setMaxPlayers($response[0]['virtualserver_maxclients']);
    }

    /**
     * Request the client list from the server.
     *
     * @throws \Exception
     */
    protected function makeClientRequest() {
        $response = $this->sendCommand('clientlist', $err, $msg);
        if ($err !== 0) {
            throw new \Exception('Client list request failed: ' . $msg);
        }

        $clients = array();
        $count = 0;
        foreach ($response as $client)
        {
            if ($client['client_type'])
            {
                continue;
            }
            $count++;
            $clients[] = $client['client_nickname'];
        }
        natcasesort($clients);

        $this->setPlayerCount($count);
        $this->setPlayerList($clients);
    }

    /**
     * Send a command to the server.
     *
     * @param string $command The command to send
     * @param int &$err Variable to hold the error code
     * @param string &$msg Variable to hold the error message
     * @return mixed[] Array of associative arrays representing response rows
     */
    protected function sendCommand($command, &$err = null, &$msg = null) {
        $command = trim($command);

        if (empty($command))
        {
            return null;
        }

        if(fwrite($this->fp, $command . "\r\n") === false)
        {
            throw new \Exception('Failed to write to socket');
        }

        $sockets = array($this->fp);
        if (!stream_select($sockets, $write, $except, $this->timeout))
        {
            throw new \Exception('Connection timed out');
        }
        return self::parseResponse(stream_get_contents($this->fp), $err, $msg);
    }

    /**
     * Parse a response from the server.
     *
     * @param string $response The raw response
     * @param int &$err Variable to hold the error code
     * @param string &$msg Variable to hold the error message
     * @return mixed[] Array of associative arrays representing response rows
     */
    protected static function parseResponse($response, &$err, &$msg) {
        $response = trim($response);
        if (empty($response))
        {
            return null;
        }

        $parts = explode("\n", $response);
        $error = trim(array_pop($parts));
        $response = array_pop($parts);

        sscanf($error, 'error id=%d msg=%s', $err, $msg);

        if (empty($response))
        {
            return array();
        }

        $ret = array();
        foreach (explode('|', $response) as $i => $row)
        {
            foreach (explode(' ', $row) as $kv)
            {
                $kv = explode('=', $kv);
                $ret[$i][$kv[0]] = self::unescape(isset($kv[1]) ? $kv[1] : '');
            }
        }

        return $ret;
    }

    /**
     * Escape a string for use as a command parameter.
     *
     * @param string $str The string to escape
     * @return string The escaped string
     */
    protected static function escape($str) {
        return str_replace(self::$chars, self::$replace, $str);
    }

    /**
     * Unescape a string for use as a command parameter.
     *
     * @param string $str The string to unescape
     * @return string The unescaped string
     */
    protected static function unescape($str) {
        return str_replace(self::$replace, self::$chars, $str);
    }

}
