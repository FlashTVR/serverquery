<?php

/*
 * The MIT License
 *
 * Copyright 2016 Steve Guidetti.
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
 * The caching facility for the application
 * 
 * @author Steve Guidetti
 */
class Cache {

    /**
     * The Memcached object if available
     *
     * @var \Memcached
     */
    private $memcached;

    /**
     * Constructor
     * 
     * @throws \RuntimeException if the cache directory is not writable
     */
    public function __construct() {
        if(class_exists('Memcached')) {
            $this->memcached = new \Memcached();
            $this->memcached->addServer('127.0.0.1', 11211);
        } else {
            $dir = __DIR__ . '/../cache';
            if(!is_dir($dir) || !is_writable($dir)) {
                throw new \RuntimeException('The cache directory is not writable');
            }
        }
    }

    /**
     * Load cached data into a Gameserver object
     *
     * @param \SQ\Gameserver $server
     * @return bool false if object is not found or data is invalid
     */
    public function get(Gameserver $server) {
        $data = false;
        if($this->memcached != null) {
            $data = $this->memcached->get(self::getKey($server));
        } else {
            $file = self::getFileName($server);
            $data = file_exists($file) ? file_get_contents($file) : false;
        }
        return $server->fromJSON($data);
    }

    /**
     * Store a Gameserver object in the cache
     *
     * @param \SQ\Gameserver $server
     */
    public function put(Gameserver $server) {
        if($this->memcached != null) {
            $this->memcached->set(self::getKey($server), $server->toJSON());
        } else {
            file_put_contents(self::getFileName($server), $server->toJSON());
        }
    }

    /**
     * Get the cache key based on a Gameserver object
     *
     * @param \SQ\Gameserver $server
     * @return string
     */
    private static function getKey(Gameserver $server) {
        return 'sq_' . $server->getGameId() . '_' . str_replace(':', '_', $server->getAddress());
    }

    /**
     * Get the name of a cache file based on a Gameserver object
     *
     * @param \SQ\Gameserver $server
     * @return string
     */
    private static function getFileName(Gameserver $server) {
        return __DIR__ . '/../cache/' . self::getKey($server) . '.json';
    }

}
