<?php declare(strict_types=1);

namespace Aikinomi\Wbsng\Model\Config;

use Aikinomi\Wbsng\Common\Decimal;
use Aikinomi\Wbsng\Mapping\Context;
use Aikinomi\Wbsng\Mapping\T;


/**
 * @psalm-immutable
 */
class Range
{
    use RangeMapping;


    /**
     * @readonly
     * @var ?Decimal
     */
    public $min;

    /**
     * @readonly
     * @var ?Decimal
     */
    public $max;


    public function __construct(?Decimal $min, ?Decimal $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function includes(Decimal $value): bool
    {
        return
            (!isset($this->min) || $value->isGreaterThanOrEqualTo($this->min)) &&
            (!isset($this->max) || $value->isLessThan($this->max));
    }
}


trait RangeMapping
{
    public static function unserialize(?array $data, ?callable $convert = null): self
    {
        if (!isset($data)) {
            self::$noop = new self(null, null);
            return self::$noop;
        }

        $data = Context::of($data);

        $convert = $convert ?? function($x) { return $x; };
        $bound = function($v) use($convert) {
            return $v === null ? null : $convert(T::decimal($v));
        };
        
        return new self(
            $data['min']->map($bound),
            $data['max']->map($bound)
        );
    }

    private static $noop;
}