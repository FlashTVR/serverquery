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
 * Buffer for Valve query responses
 *
 * @author Steve Guidetti
 */
class ValveBuffer {
    private $buffer = '';
    private $length = 0;
    private $position = 0;

    public function set($buffer) {
        $this->buffer = $buffer;
        $this->length = strlen($buffer);
        $this->position = 0;
    }

    public function reset() {
        $this->buffer = '';
        $this->length = $this->position = 0;
    }

    public function setPosition($pos) {
        if(is_int($pos)) {
            $this->position = $pos;
        }
    }

    public function remaining() {
        return $this->length - $this->position;
    }

    public function get($len = -1) {
        if($len == 0) {
            return '';
        }

        $rem = $this->remaining();

        if($len == -1) {
            $len = $rem;
        } else if($len > $rem) {
            return '';
        }

        $data = substr($this->buffer, $this->position, $len);
        $this->position += $len;
        return $data;
    }

    public function getByte() {
        return ord($this->get(1));
    }

    public function getShort() {
        $data = unpack('v', $this->get(2));
        return $data[1];
    }

    public function getLong() {
        $data = unpack('l', $this->get(4));
        return $data[1];
    }

    public function getFloat() {
        $data = unpack('f', $this->get(4));
        return $data[1];
    }

    public function getLongLong() {
        $data = unpack('P', $this->get(8));
        return $data[1];
    }

    public function getString() {
        $nullByte = strpos($this->buffer, "\0", $this->position);

        if($nullByte === false) {
            return '';
        } else {
            $string = $this->get($nullByte - $this->position);
            $this->position++;
            return $string;
        }
    }
}
