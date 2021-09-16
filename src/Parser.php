<?php

declare(strict_types=1);

namespace GeoIO\WKB\Parser;

use GeoIO\Coordinates;
use GeoIO\Dimension;
use GeoIO\Factory;
use GeoIO\WKB\Parser\Exception\ParserException;
use RuntimeException;
use Throwable;

final class Parser
{
    private const MASK_SRID = 0x20000000;
    private const MASK_Z = 0x80000000;
    private const MASK_M = 0x40000000;

    private const TYPE_POINT = 1;
    private const TYPE_LINESTRING = 2;
    private const TYPE_POLYGON = 3;
    private const TYPE_MULTIPOINT = 4;
    private const TYPE_MULTILINESTRING = 5;
    private const TYPE_MULTIPOLYGON = 6;
    private const TYPE_GEOMETRYCOLLECTION = 7;

    private Factory $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function parse(string $str): mixed
    {
        try {
            $scanner = new Scanner($str);

            return $this->doParse(
                $scanner,
                null,
                null,
                null,
            );
        } catch (Throwable $e) {
            throw new ParserException(sprintf('Parsing failed: %s', $e->getMessage()), 0, $e);
        }
    }

    private function doParse(
        Scanner $scanner,
        ?string $parentDimension,
        ?int $parentSid,
        ?int $expectedType,
    ): mixed {
        $scanner->littleEndian();

        $type = $scanner->integer();

        $srid = null;
        $hasZ = false;
        $hasM = false;

        if ($type & self::MASK_SRID) {
            $srid = $scanner->integer();
            $type &= ~self::MASK_SRID;
        }

        if ($type & self::MASK_Z) {
            $hasZ = true;
            $type &= ~self::MASK_Z;
        }

        if ($type & self::MASK_M) {
            $hasM = true;
            $type &= ~self::MASK_M;
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
            throw new RuntimeException(sprintf(
                'Unexpected geometry type %d, expected %d.',
                $type,
                $expectedType,
            ));
        }

        if (null !== $srid && null !== $parentSid && $srid !== $parentSid) {
            // @codeCoverageIgnoreStart
            // Just to be safe. This usually can't happen as WKB has the SRID
            // only set for the top level geometry.
            throw new RuntimeException(sprintf(
                'SRID mismatch between %d and expected %d.',
                $srid,
                $parentSid,
            ));
            // @codeCoverageIgnoreEnd
        }

        if ($parentDimension && $dimension !== $parentDimension) {
            throw new RuntimeException(sprintf(
                'Dimension mismatch between %s and expected %s.',
                $dimension,
                $parentDimension,
            ));
        }

        return $this->geometry(
            $scanner,
            $dimension,
            $srid ?? $parentSid,
            $type,
        );
    }

    private function geometry(
        Scanner $scanner,
        string $dimension,
        ?int $srid,
        int $type,
    ): mixed {
        switch ($type) {
            case self::TYPE_POINT:
                return $this->point(
                    $scanner,
                    $dimension,
                    $srid,
                );
            case self::TYPE_LINESTRING:
                return $this->lineString(
                    $scanner,
                    $dimension,
                    $srid,
                );
            case self::TYPE_POLYGON:
                $num = $scanner->integer();
                $linearRings = [];
                for ($i = 0; $i < $num; ++$i) {
                    $linearRings[] = $this->lineString(
                        $scanner,
                        $dimension,
                        $srid,
                        true,
                    );
                }

                return $this->factory->createPolygon(
                    $dimension,
                    $srid,
                    $linearRings,
                );
            case self::TYPE_MULTIPOINT:
                $num = $scanner->integer();
                $points = [];
                for ($i = 0; $i < $num; ++$i) {
                    $points[] = $this->doParse(
                        $scanner,
                        $dimension,
                        $srid,
                        self::TYPE_POINT,
                    );
                }

                return $this->factory->createMultiPoint(
                    $dimension,
                    $srid,
                    $points,
                );
            case self::TYPE_MULTILINESTRING:
                $num = $scanner->integer();
                $lineStrings = [];
                for ($i = 0; $i < $num; ++$i) {
                    $lineStrings[] = $this->doParse(
                        $scanner,
                        $dimension,
                        $srid,
                        self::TYPE_LINESTRING,
                    );
                }

                return $this->factory->createMultiLineString(
                    $dimension,
                    $srid,
                    $lineStrings,
                );
            case self::TYPE_MULTIPOLYGON:
                $num = $scanner->integer();
                $polygons = [];
                for ($i = 0; $i < $num; ++$i) {
                    $polygons[] = $this->doParse(
                        $scanner,
                        $dimension,
                        $srid,
                        self::TYPE_POLYGON,
                    );
                }

                return $this->factory->createMultiPolygon(
                    $dimension,
                    $srid,
                    $polygons,
                );
            case self::TYPE_GEOMETRYCOLLECTION:
            default:
                $num = $scanner->integer();
                $geometries = [];
                for ($i = 0; $i < $num; ++$i) {
                    $geometries[] = $this->doParse(
                        $scanner,
                        $dimension,
                        $srid,
                        null,
                    );
                }

                return $this->factory->createGeometryCollection(
                    $dimension,
                    $srid,
                    $geometries,
                );
        }
    }

    private function lineString(
        Scanner $scanner,
        string $dimension,
        ?int $srid,
        bool $isLinearRing = false,
    ): mixed {
        $num = $scanner->integer();

        $points = [];
        for ($i = 0; $i < $num; ++$i) {
            $points[] = $this->point(
                $scanner,
                $dimension,
                $srid,
            );
        }

        if ($isLinearRing) {
            return $this->factory->createLinearRing(
                $dimension,
                $srid,
                $points,
            );
        }

        return $this->factory->createLineString(
            $dimension,
            $srid,
            $points,
        );
    }

    private function point(
        Scanner $scanner,
        string $dimension,
        ?int $srid,
    ): mixed {
        $coordinates = [
            'x' => $scanner->double(),
            'y' => $scanner->double(),
            'z' => null,
            'm' => null,
        ];

        if (
            Dimension::DIMENSION_3DZ === $dimension ||
            Dimension::DIMENSION_4D === $dimension
        ) {
            $coordinates['z'] = $scanner->double();
        }

        if (
            Dimension::DIMENSION_3DM === $dimension ||
            Dimension::DIMENSION_4D === $dimension
        ) {
            $coordinates['m'] = $scanner->double();
        }

        return $this->factory->createPoint(
            $dimension,
            $srid,
            new Coordinates(...$coordinates),
        );
    }
}
