Geo I/O WKB Parser
==================

[![Build Status](https://travis-ci.org/geo-io/wkb-parser.svg?branch=master)](https://travis-ci.org/geo-io/wkb-parser)
[![Coverage Status](https://img.shields.io/coveralls/geo-io/wkb-parser.svg?style=flat)](https://coveralls.io/r/geo-io/wkb-parser)

A parser which transforms
[Well-known binary (WKB)](http://en.wikipedia.org/wiki/Well-known_text#Well-known_binary)
representations into geometric objects.

```php
class MyFactory implements GeoIO\Factory
{
    public function createPoint($dimension, array $coordinates, $srid = null)
    {
        return MyPoint($coordinates['x'], $coordinates['y']);
    }

    // ...
}

$factory = MyFactory();
$parser = new GeoIO\WKB\Parser\Parser($factory);

$myPoint = $parser->parse('000000000140000000000000004010000000000000'); // POINT(2.0 4.0)
```

Installation
------------

Install [through composer](http://getcomposer.org). Check the
[packagist page](https://packagist.org/packages/geo-io/wkb-parser) for all
available versions.

```bash
composer require geo-io/wkb-parser
```

License
-------

Geo I/O WKB Parser is released under the [MIT License](LICENSE).
