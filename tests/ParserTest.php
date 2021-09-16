<?php

declare(strict_types=1);

namespace GeoIO\WKB\Parser;

use GeoIO\Coordinates;
use GeoIO\Dimension;
use GeoIO\Factory;
use GeoIO\WKB\Parser\Exception\ParserException;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private function coords(
        float $x,
        float $y,
        ?float $z = null,
        ?float $m = null,
    ): Coordinates {
        return new Coordinates(
            x: $x,
            y: $y,
            z: $z,
            m: $m,
        );
    }

    public function testPointXdrHex(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_2D, null, $this->coords(1, 2));

        $parser = new Parser($factory);
        $parser->parse('00000000013ff00000000000004000000000000000');
    }

    public function testPointXdrBinary(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_2D, null, $this->coords(1, 2));

        $parser = new Parser($factory);
        $parser->parse(pack('H*', '00000000013ff00000000000004000000000000000'));
    }

    public function testPointNdr(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_2D, null, $this->coords(1, 2));

        $parser = new Parser($factory);
        $parser->parse('0101000000000000000000f03f0000000000000040');
    }

    public function testPointEwkbZ(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_3DZ, null, $this->coords(1, 2, 3));

        $parser = new Parser($factory);
        $parser->parse('00800000013ff000000000000040000000000000004008000000000000');
    }

    public function testPointEwkbM(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_3DM, null, $this->coords(1, 2, null, 3));

        $parser = new Parser($factory);
        $parser->parse('00400000013ff000000000000040000000000000004008000000000000');
    }

    public function testPointEwkbZM(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_4D, null, $this->coords(1, 2, 3, 4));

        $parser = new Parser($factory);
        $parser->parse('00c00000013ff0000000000000400000000000000040080000000000004010000000000000');
    }

    public function testPointWkb12Z(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_3DZ, null, $this->coords(1, 2, 3));

        $parser = new Parser($factory);
        $parser->parse('00000003e93ff000000000000040000000000000004008000000000000');
    }

    public function testPointWkb12M(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_3DM, null, $this->coords(1, 2, null, 3));

        $parser = new Parser($factory);
        $parser->parse('00000007d13ff000000000000040000000000000004008000000000000');
    }

    public function testPointWkb12ZM(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_4D, null, $this->coords(1, 2, 3, 4));

        $parser = new Parser($factory);
        $parser->parse('0000000bb93ff0000000000000400000000000000040080000000000004010000000000000');
    }

    public function testPointWkb12ZWithoutEnoughData(): void
    {
        $parser = new Parser($this->createMock(Factory::class));

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Parsing failed: Not enough bytes left to fulfill 1 double.');

        $parser->parse('00000003e93ff00000000000004000000000000000');
    }

    public function testPointEwkbZWithSrid(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPoint')
            ->with(Dimension::DIMENSION_3DZ, 1000, $this->coords(1, 2, 3));

        $parser = new Parser($factory);
        $parser->parse('00a0000001000003e83ff000000000000040000000000000004008000000000000');
    }

    public function testLineString(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(3))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
                [Dimension::DIMENSION_2D, null, $this->coords(5, 6)],
            );

        $parser = new Parser($factory);
        $parser->parse('0000000002000000033ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEwkbZ(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_3DZ, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_3DZ, null, $this->coords(1, 2, 3)],
                [Dimension::DIMENSION_3DZ, null, $this->coords(4, 5, 6)],
            );

        $parser = new Parser($factory);
        $parser->parse('0080000002000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEwkbZAndSrid(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_3DZ, 1000, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_3DZ, 1000, $this->coords(1, 2, 3)],
                [Dimension::DIMENSION_3DZ, 1000, $this->coords(4, 5, 6)],
            );

        $parser = new Parser($factory);
        $parser->parse('00a0000002000003e8000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringWkb12Z(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_3DZ, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_3DZ, null, $this->coords(1, 2, 3)],
                [Dimension::DIMENSION_3DZ, null, $this->coords(4, 5, 6)],
            );

        $parser = new Parser($factory);
        $parser->parse('00000003ea000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000200000000');
    }

    public function testPolygon(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPolygon')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->once())
            ->method('createLinearRing')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(4))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
                [Dimension::DIMENSION_2D, null, $this->coords(6, 5)],
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
            );

        $parser = new Parser($factory);
        $parser->parse('000000000300000001000000043ff0000000000000400000000000000040080000000000004010000000000000401800000000000040140000000000003ff00000000000004000000000000000');
    }

    public function testPolygonEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createPolygon')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000300000000');
    }

    public function testMultiPoint(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPoint')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
            );

        $parser = new Parser($factory);
        $parser->parse('00000000040000000200000000013ff00000000000004000000000000000000000000140080000000000004010000000000000');
    }

    public function testMultiPointEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPoint')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000400000000');
    }

    public function testMultiPointMixedByteOrder(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPoint')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
            );

        $parser = new Parser($factory);
        $parser->parse('0000000004000000020101000000000000000000f03f0000000000000040000000000140080000000000004010000000000000');
    }

    public function testMultiPointWithEwkbZ(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPoint')
            ->with(Dimension::DIMENSION_3DZ, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_3DZ, null, $this->coords(1, 2, 5)],
                [Dimension::DIMENSION_3DZ, null, $this->coords(3, 4, 6)],
            );

        $parser = new Parser($factory);
        $parser->parse('00800000040000000200800000013ff0000000000000400000000000000040140000000000000080000001400800000000000040100000000000004018000000000000');
    }

    public function testMultiPointWithMixedZ(): void
    {
        $parser = new Parser($this->createMock(Factory::class));

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Parsing failed: Dimension mismatch between 2D and expected 3DZ.');

        $parser->parse('00800000040000000200800000013ff000000000000040000000000000004014000000000000000000000140080000000000004010000000000000');
    }

    /**
     * @see Issue #3
     */
    public function testMultiPointWithSrid(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPoint')
            ->with(Dimension::DIMENSION_2D, 4326, $this->anything());

        $factory
            ->expects($this->exactly(7))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, 4326, $this->coords(-67, -9)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(-23, 53)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(83, 19)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(22, 74)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(14, -83)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(-147, 44)],
                [Dimension::DIMENSION_2D, 4326, $this->coords(-37, -55)],
            );

        $parser = new Parser($factory);
        $parser->parse('0104000020E61000000700000001010000000000000000C050C000000000000022C0010100000000000000000037C00000000000804A4001010000000000000000C05440000000000000334001010000000000000000003640000000000080524001010000000000000000002C400000000000C054C0010100000000000000006062C00000000000004640010100000000000000008042C00000000000804BC0');
    }

    public function testMultiLineString(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiLineString')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createLineString')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->anything()],
            );

        $factory
            ->expects($this->exactly(5))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
                [Dimension::DIMENSION_2D, null, $this->coords(5, 6)],
                [Dimension::DIMENSION_2D, null, $this->coords(-1, -2)],
                [Dimension::DIMENSION_2D, null, $this->coords(-3, -4)],
            );

        $parser = new Parser($factory);
        $parser->parse('0000000005000000020000000002000000033ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000000000000200000002bff0000000000000c000000000000000c008000000000000c010000000000000');
    }

    public function testMultiLineStringEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiLineString')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000500000000');
    }

    public function testMultiLineStringWrongType(): void
    {
        $parser = new Parser($this->createMock(Factory::class));

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Parsing failed: Unexpected geometry type 1, expected 2.');

        $parser->parse('0000000005000000020000000002000000033ff00000000000004000000000000000400800000000000040100000000000004014000000000000401800000000000000000000013ff00000000000004000000000000000');
    }

    public function testMultiPolygon(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPolygon')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(2))
            ->method('createPolygon')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->anything()],
                [Dimension::DIMENSION_2D, null, $this->anything()],
            );
        $factory
            ->expects($this->once())
            ->method('createLinearRing')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(4))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
                [Dimension::DIMENSION_2D, null, $this->coords(6, 5)],
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
            );

        $parser = new Parser($factory);
        $parser->parse('000000000600000002000000000300000001000000043ff0000000000000400000000000000040080000000000004010000000000000401800000000000040140000000000003ff00000000000004000000000000000000000000300000000');
    }

    public function testMultiPolygonEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createMultiPolygon')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000600000000');
    }

    public function testGeometryCollection(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createGeometryCollection')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->once())
            ->method('createLineString')
            ->with(Dimension::DIMENSION_2D, null, $this->anything());

        $factory
            ->expects($this->exactly(4))
            ->method('createPoint')
            ->withConsecutive(
                [Dimension::DIMENSION_2D, null, $this->coords(1, 2)],
                [Dimension::DIMENSION_2D, null, $this->coords(3, 4)],
                [Dimension::DIMENSION_2D, null, $this->coords(5, 6)],
                [Dimension::DIMENSION_2D, null, $this->coords(-1, -2)],
            );

        $parser = new Parser($factory);
        $parser->parse('0000000007000000020000000002000000033ff0000000000000400000000000000040080000000000004010000000000000401400000000000040180000000000000000000001bff0000000000000c000000000000000');
    }

    public function testGeometryCollectionEmpty(): void
    {
        $factory = $this->createMock(Factory::class);

        $factory
            ->expects($this->once())
            ->method('createGeometryCollection')
            ->with(Dimension::DIMENSION_2D, null, []);

        $parser = new Parser($factory);
        $parser->parse('000000000700000000');
    }

    public function testBadEndianValue(): void
    {
        $parser = new Parser($this->createMock(Factory::class));

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Parsing failed: Bad endian byte value 3.');

        $parser->parse('03010000003D0AD7A3701D41400000000000C055C0');
    }
}
