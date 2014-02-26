<?php

namespace GeoIO\WKB\Parser;

class Scanner
{
    private $data;
    private $len;
    private $pos;

    public function __construct($data)
    {
        if (preg_match('/[0-9a-fA-F]+/', $data[0])) {
            $data = pack('H*', $data);
        }

        $this->data = $data;
        $this->len = strlen($data);
        $this->pos = 0;
    }

    public function remaining()
    {
        return $this->len - $this->pos;
    }

    public function byte()
    {
        if ($this->pos + 1 > $this->len) {
            throw new \RuntimeException('Not enough bytes left to fulfill 1 byte.');
        }

        $str = substr($this->data, $this->pos, 1);
        $this->pos += 1;

        $result = unpack('C', $str);

        return $result[1];
    }

    public function integer($litteEndian)
    {
        if ($this->pos + 4 > $this->len) {
            throw new \RuntimeException('Not enough bytes left to fulfill 1 integer.');
        }

        $str = substr($this->data, $this->pos, 4);
        $this->pos += 4;

        $result = unpack($litteEndian ? 'V' : 'N', $str);

        return $result[1];
    }

    public function double($litteEndian)
    {
        if ($this->pos + 8 > $this->len) {
            throw new \RuntimeException('Not enough bytes left to fulfill 1 double.');
        }

        $str = substr($this->data, $this->pos, 8);
        $this->pos += 8;

        if (!$litteEndian) {
            $str = strrev($str);
        }

        $double = unpack('d', $str);

        return $double[1];
    }
}
