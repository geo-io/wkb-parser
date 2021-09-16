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
        $litteEndian = $this->isLittleEndian($scanner);

        $type = $scanner->integer($litteEndian);

        $srid = null;
        $hasZ = false;
        $hasM = false;

        if ($type & self::MASK_SRID) {
            $srid = $scanner->integer($litteEndian);
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
                'Unexpected geometry type %s, expected %s.',
                json_encode($type),
                json_encode($expectedType),
            ));
        }

        if (null !== $srid && null !== $parentSid && $srid !== $parentSid) {
            // @codeCoverageIgnoreStart
            // Just to be safe. This usually can't happen as WKB has the SRID
            // only set for the top level geometry.
            throw new RuntimeException(sprintf(
                'SRID mismatch between %s and expected %s.',
                json_encode($srid),
                json_encode($parentSid),
            ));
            // @codeCoverageIgnoreEnd
        }

        if ($parentDimension && $dimension !== $parentDimension) {
            throw new RuntimeException(sprintf(
                'Dimension mismatch between %s and expected %s.',
                json_encode($dimension),
                json_encode($parentDimension),
            ));
        }

        return $this->geometry(
            $scanner,
            $litteEndian,
            $dimension,
            $srid ?? $parentSid,
            $type,
        );
    }

    private function isLittleEndian(
        Scanner $scanner,
    ): bool {
        $endianValue = $scanner->byte();

        return match ($endianValue) {
            0 => false,
            1 => true,
            default => throw new RuntimeException(sprintf('Bad endian byte value %s.', json_encode($endianValue))),
        };
    }

    private function geometry(
        Scanner $scanner,
        bool $litteEndian,
        string $dimension,
        ?int $srid,
        int $type,
    ): mixed {
        switch ($type) {
            case self::TYPE_POINT:
                return $this->point(
                    $scanner,
                    $litteEndian,
                    $dimension,
                    $srid,
                );
            case self::TYPE_LINESTRING:
                return $this->lineString(
                    $scanner,
                    $litteEndian,
                    $dimension,
                    $srid,
                );
            case self::TYPE_POLYGON:
                $num = $scanner->integer($litteEndian);
                $linearRings = [];
                for ($i = 0; $i < $num; ++$i) {
                    $linearRings[] = $this->lineString(
                        $scanner,
                        $litteEndian,
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
                $num = $scanner->integer($litteEndian);
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
                $num = $scanner->integer($litteEndian);
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
                $num = $scanner->integer($litteEndian);
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
                $num = $scanner->integer($litteEndian);
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
        bool $litteEndian,
        string $dimension,
        ?int $srid,
        bool $isLinearRing = false,
    ): mixed {
        $num = $scanner->integer($litteEndian);

        $points = [];
        for ($i = 0; $i < $num; ++$i) {
            $points[] = $this->point(
                $scanner,
                $litteEndian,
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
        bool $litteEndian,
        string $dimension,
        ?int $srid,
    ): mixed {
        $coordinates = [
            'x' => $scanner->double($litteEndian),
            'y' => $scanner->double($litteEndian),
            'z' => null,
            'm' => null,
        ];

        if (
            Dimension::DIMENSION_3DZ === $dimension ||
            Dimension::DIMENSION_4D === $dimension
        ) {
            $coordinates['z'] = $scanner->double($litteEndian);
        }

        if (
            Dimension::DIMENSION_3DM === $dimension ||
            Dimension::DIMENSION_4D === $dimension
        ) {
            $coordinates['m'] = $scanner->double($litteEndian);
        }

        return $this->factory->createPoint(
            $dimension,
            $srid,
            new Coordinates(...$coordinates),
        );
    }
}
