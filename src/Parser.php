<?php

namespace GeoIO\WKB\Parser;

use GeoIO\Dimension;
use GeoIO\Factory;
use GeoIO\WKB\Parser\Exception\ParserException;

class Parser
{
    const MASK_SRID = 0x20000000;
    const MASK_Z = 0x80000000;
    const MASK_M = 0x40000000;

    const TYPE_POINT = 1;
    const TYPE_LINESTRING = 2;
    const TYPE_POLYGON = 3;
    const TYPE_MULTIPOINT = 4;
    const TYPE_MULTILINESTRING = 5;
    const TYPE_MULTIPOLYGON = 6;
    const TYPE_GEOMETRYCOLLECTION = 7;

    private $scanner;

    private $factory;

    private $dimension;
    private $srid;
    private $litteEndian;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function parse($str)
    {
        try {
            $this->scanner = new Scanner($str);

            return $this->doParse();
        } catch (\Exception $e) {
            throw new ParserException(sprintf('Parsing failed: %s', $e->getMessage()), 0, $e);
        }
    }

    protected function doParse($expectedType = null)
    {
        $this->endian();

        $type = $this->scanner->integer($this->litteEndian);

        $srid = null;
        $hasZ = false;
        $hasM = false;

        if ($type & self::MASK_SRID) {
            $srid = $this->scanner->integer($this->litteEndian);
            $type = ($type & ~self::MASK_SRID);
        }

        if ($type & self::MASK_Z) {
            $hasZ = true;
            $type = ($type & ~self::MASK_Z);
        }

        if ($type & self::MASK_M) {
            $hasM = true;
            $type = ($type & ~self::MASK_M);
        }

        $wkb12 = false;

        if (($type / 1000) & 1) {
            $hasZ = true;
            $wkb12 = true;
        }

        if (($type / 1000) & 2) {
            $hasM = true;
            $wkb12 = true;
        }

        if ($wkb12) {
            $type %= 1000;
        }

        if ($hasZ && $hasM) {
            $dimension = Dimension::DIMENSION_4D;
        } elseif ($hasM) {
            $dimension = Dimension::DIMENSION_3DM;
        } elseif ($hasZ) {
            $dimension = Dimension::DIMENSION_3DZ;
        } else {
            $dimension = Dimension::DIMENSION_2D;
        }

        if ($expectedType && $expectedType !== $type) {
            throw new \RuntimeException(sprintf(
                'Unexpected geometry type %s, expected %s.',
                json_encode($type),
                json_encode($expectedType)
            ));
        }

        if (null !== $srid && null !== $this->srid && $srid !== $this->srid) {
            throw new \RuntimeException(sprintf(
                'SRID mismatch between %s and expected %s.',
                json_encode($srid),
                json_encode($this->srid)
            ));
        }

        if ($this->dimension && $dimension !== $this->dimension) {
            throw new \RuntimeException(sprintf(
                'Dimension mismatch between %s and expected %s.',
                json_encode($dimension),
                json_encode($this->dimension)
            ));
        }

        if (null !== $srid) {
            $this->srid = $srid;
        }

        $this->dimension = $dimension;

        return $this->geometry($type);
    }

    protected function endian()
    {
        $endianValue = $this->scanner->byte();

        switch ($endianValue) {
            case 0:
                $this->litteEndian = false;
                break;
            case 1:
                $this->litteEndian = true;
                break;
            default:
                throw new \RuntimeException(sprintf('Bad endian byte value %s.', json_encode($endianValue)));
        }
    }

    protected function geometry($type)
    {
        switch ($type) {
            case self::TYPE_POINT:
                return $this->point();
            case self::TYPE_LINESTRING:
                return $this->lineString();
            case self::TYPE_POLYGON:
                $num = $this->scanner->integer($this->litteEndian);
                $linearRings = array();
                for ($i = 0; $i < $num; $i++) {
                    $linearRings[] = $this->lineString(true);
                }

                return $this->factory->createPolygon(
                    $this->dimension,
                    $linearRings,
                    $this->srid
                );
            case self::TYPE_MULTIPOINT:
                $num = $this->scanner->integer($this->litteEndian);
                $points = array();
                for ($i = 0; $i < $num; $i++) {
                    $points[] = $this->doParse(self::TYPE_POINT);
                }

                return $this->factory->createMultiPoint(
                    $this->dimension,
                    $points,
                    $this->srid
                );
            case self::TYPE_MULTILINESTRING:
                $num = $this->scanner->integer($this->litteEndian);
                $lineStrings = array();
                for ($i = 0; $i < $num; $i++) {
                    $lineStrings[] = $this->doParse(self::TYPE_LINESTRING);
                }

                return $this->factory->createMultiLineString(
                    $this->dimension,
                    $lineStrings,
                    $this->srid
                );
            case self::TYPE_MULTIPOLYGON:
                $num = $this->scanner->integer($this->litteEndian);
                $polygons = array();
                for ($i = 0; $i < $num; $i++) {
                    $polygons[] = $this->doParse(self::TYPE_POLYGON);
                }

                return $this->factory->createMultiPolygon(
                    $this->dimension,
                    $polygons,
                    $this->srid
                );
            default:
                $num = $this->scanner->integer($this->litteEndian);
                $geometries = array();
                for ($i = 0; $i < $num; $i++) {
                    $geometries[] = $this->doParse();
                }

                return $this->factory->createGeometryCollection(
                    $this->dimension,
                    $geometries,
                    $this->srid
                );
        }
    }

    protected function lineString($isLinearRing = false)
    {
        $num = $this->scanner->integer($this->litteEndian);

        $points = array();
        for ($i = 0; $i < $num; $i++) {
            $points[] = $this->point();
        }

        if ($isLinearRing) {
            return $this->factory->createLinearRing(
                $this->dimension,
                $points,
                $this->srid
            );
        }

        return $this->factory->createLineString(
            $this->dimension,
            $points,
            $this->srid
        );
    }

    protected function point()
    {
        $coordinates = array(
            'x' => $this->scanner->double($this->litteEndian),
            'y' => $this->scanner->double($this->litteEndian),
            'z' => null,
            'm' => null
        );

        if (Dimension::DIMENSION_3DZ === $this->dimension ||
            Dimension::DIMENSION_4D === $this->dimension) {
            $coordinates['z'] = $this->scanner->double($this->litteEndian);
        }

        if (Dimension::DIMENSION_3DM === $this->dimension ||
            Dimension::DIMENSION_4D === $this->dimension) {
            $coordinates['m'] = $this->scanner->double($this->litteEndian);
        }

        return $this->factory->createPoint(
            $this->dimension,
            $coordinates,
            $this->srid
        );
    }
}
