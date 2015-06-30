<?php

namespace GeoIO\WKB\Parser;

use GeoIO\Dimension;
use Mockery;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected function coords($x, $y, $z = null, $m = null)
    {
        return array(
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'm' => $m
        );
    }

    public function testPointXdrHex()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00000000013ff00000000000004000000000000000');
    }

    public function testPointXdrBinary()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $parser = new Parser($factory);
        $parser->parse(pack('H*', '00000000013ff00000000000004000000000000000'));
    }

    public function testPointNdr()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0101000000000000000000f03f0000000000000040');
    }

    public function testPointEwkbZ()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00800000013ff000000000000040000000000000004008000000000000');
    }

    public function testPointEwkbM()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DM, $this->coords(1, 2, null, 3), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00400000013ff000000000000040000000000000004008000000000000');
    }

    public function testPointEwkbZM()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_4D, $this->coords(1, 2, 3, 4), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00c00000013ff0000000000000400000000000000040080000000000004010000000000000');
    }

    public function testPointWkb12Z()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00000003e93ff000000000000040000000000000004008000000000000');
    }

    public function testPointWkb12M()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DM, $this->coords(1, 2, null, 3), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00000007d13ff000000000000040000000000000004008000000000000');
    }

    public function testPointWkb12ZM()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_4D, $this->coords(1, 2, 3, 4), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0000000bb93ff0000000000000400000000000000040080000000000004010000000000000');
    }

    public function testPointWkb12ZWithoutEnoughData()
    {
        $this->setExpectedException('GeoIO\WKB\Parser\Exception\ParserException', 'Parsing failed: Not enough bytes left to fulfill 1 double.');

        $parser = new Parser(Mockery::mock('GeoIO\\Factory')->shouldIgnoreMissing());
        $parser->parse('00000003e93ff00000000000004000000000000000');
    }

    public function testPointEwkbZWithSrid()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), 1000)
        ;

        $parser = new Parser($factory);
        $parser->parse('00a0000001000003e83ff000000000000040000000000000004008000000000000');
    }

    public function testLineString()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(5, 6), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0000000002000000033ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEwkbZ()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(4, 5, 6), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0080000002000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEwkbZAndSrid()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, Mockery::any(), 1000)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), 1000)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(4, 5, 6), 1000)
        ;

        $parser = new Parser($factory);
        $parser->parse('00a0000002000003e8000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringWkt12Z()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 3), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(4, 5, 6), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00000003ea000000023ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000');
    }

    public function testLineStringEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000200000000');
    }

    public function testPolygon()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createLinearRing')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->times(2)
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(6, 5), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000300000001000000043ff0000000000000400000000000000040080000000000004010000000000000401800000000000040140000000000003ff00000000000004000000000000000');
    }

    public function testPolygonEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000300000000');
    }

    public function testMultiPoint()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00000000040000000200000000013ff00000000000004000000000000000000000000140080000000000004010000000000000');
    }

    public function testMultiPointEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000400000000');
    }

    public function testMultiPointMixedByteOrder()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0000000004000000020101000000000000000000f03f0000000000000040000000000140080000000000004010000000000000');
    }

    public function testMultiPointWithEwkbZ()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(1, 2, 5), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_3DZ, $this->coords(3, 4, 6), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('00800000040000000200800000013ff0000000000000400000000000000040140000000000000080000001400800000000000040100000000000004018000000000000');
    }

    public function testMultiPointWithMixedZ()
    {
        $this->setExpectedException('GeoIO\WKB\Parser\Exception\ParserException', 'Parsing failed: Dimension mismatch between "2D" and expected "3DZ".');

        $parser = new Parser(Mockery::mock('GeoIO\\Factory')->shouldIgnoreMissing());
        $parser->parse('00800000040000000200800000013ff000000000000040000000000000004014000000000000000000000140080000000000004010000000000000');
    }

    /**
     * @see Issue #3
     */
    public function testMultiPointWithSrid()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-67, -9), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-23, 53), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(83, 19), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(22, 74), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(14, -83), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-147, 44), 4326)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-37, -55), 4326)
        ;

        $parser = new Parser($factory);
        $parser->parse('0104000020E61000000700000001010000000000000000C050C000000000000022C0010100000000000000000037C00000000000804A4001010000000000000000C05440000000000000334001010000000000000000003640000000000080524001010000000000000000002C400000000000C054C0010100000000000000006062C00000000000004640010100000000000000008042C00000000000804BC0');
    }

    public function testMultiLineString()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiLineString')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createLineString')
            ->times(2)
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-1, -2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(5, 6), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-3, -4), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0000000005000000020000000002000000033ff000000000000040000000000000004008000000000000401000000000000040140000000000004018000000000000000000000200000002bff0000000000000c000000000000000c008000000000000c010000000000000');
    }

    public function testMultiLineStringEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiLineString')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000500000000');
    }

    public function testMultiLineStringWrongType()
    {
        $this->setExpectedException('GeoIO\WKB\Parser\Exception\ParserException', 'Parsing failed: Unexpected geometry type 1, expected 2.');

        $parser = new Parser(Mockery::mock('GeoIO\\Factory')->shouldIgnoreMissing());
        $parser->parse('0000000005000000020000000002000000033ff00000000000004000000000000000400800000000000040100000000000004014000000000000401800000000000000000000013ff00000000000004000000000000000');
    }

    public function testMultiPolygon()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $factory
            ->shouldReceive('createPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createLinearRing')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->times(2)
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(6, 5), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000600000002000000000300000001000000043ff0000000000000400000000000000040080000000000004010000000000000401800000000000040140000000000003ff00000000000004000000000000000000000000300000000');
    }

    public function testMultiPolygonEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createMultiPolygon')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000600000000');
    }

    public function testGeometryCollection()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createGeometryCollection')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(-1, -2), null)
        ;

        $factory
            ->shouldReceive('createLineString')
            ->once()
            ->with(Dimension::DIMENSION_2D, Mockery::any(), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(1, 2), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(3, 4), null)
        ;

        $factory
            ->shouldReceive('createPoint')
            ->once()
            ->with(Dimension::DIMENSION_2D, $this->coords(5, 6), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('0000000007000000020000000002000000033ff0000000000000400000000000000040080000000000004010000000000000401400000000000040180000000000000000000001bff0000000000000c000000000000000');
    }

    public function testGeometryCollectionEmpty()
    {
        $factory = Mockery::mock('GeoIO\\Factory');

        $factory
            ->shouldReceive('createGeometryCollection')
            ->once()
            ->with(Dimension::DIMENSION_2D, array(), null)
        ;

        $parser = new Parser($factory);
        $parser->parse('000000000700000000');
    }

    public function testBadEndianValue()
    {
        $this->setExpectedException('GeoIO\WKB\Parser\Exception\ParserException', 'Parsing failed: Bad endian byte value 3.');

        $parser = new Parser(Mockery::mock('GeoIO\\Factory')->shouldIgnoreMissing());
        $parser->parse('03010000003D0AD7A3701D41400000000000C055C0');
    }
}
