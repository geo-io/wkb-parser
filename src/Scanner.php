<?php

declare(strict_types=1);

namespace GeoIO\WKB\Parser;

use RuntimeException;
use function strlen;

final class Scanner
{
    private string $data;
    private int $len;
    private int $pos;

    public function __construct(string $data)
    {
        if (preg_match('/[0-9a-fA-F]+/', $data[0])) {
            $data = pack('H*', $data);
        }

        $this->data = $data;
        $this->len = strlen($data);
        $this->pos = 0;
    }

    public function byte(): int
    {
        if ($this->pos + 1 > $this->len) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Not enough bytes left to fulfill 1 byte.');
            // @codeCoverageIgnoreEnd
        }

        $str = $this->data[$this->pos];
        ++$this->pos;

        $result = unpack('C', $str);

        return (int) $result[1];
    }

    public function integer(bool $litteEndian): int
    {
        if ($this->pos + 4 > $this->len) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Not enough bytes left to fulfill 1 integer.');
            // @codeCoverageIgnoreEnd
        }

        $str = substr($this->data, $this->pos, 4);
        $this->pos += 4;

        $result = unpack($litteEndian ? 'V' : 'N', $str);

        return (int) $result[1];
    }

    public function double(bool $litteEndian): float
    {
        if ($this->pos + 8 > $this->len) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Not enough bytes left to fulfill 1 double.');
            // @codeCoverageIgnoreEnd
        }

        $str = substr($this->data, $this->pos, 8);
        $this->pos += 8;

        if (!$litteEndian) {
            $str = strrev($str);
        }

        $double = unpack('d', $str);

        return (float) $double[1];
    }
}
