<?php declare(strict_types=1);

namespace Aikinomi\Wbsng\Model\Config\Method;

use Aikinomi\Wbsng\Mapping\Context;
use Aikinomi\Wbsng\Mapping\T;


class PriceSettings
{
    /**
     * @var bool
     */
    public $afterTaxes;

    /**
     * @var bool
     */
    public $afterDiscounts;


    public function __construct(bool $afterTaxes = false, bool $afterDiscounts = true)
    {
        $this->afterTaxes = $afterTaxes;
        $this->afterDiscounts = $afterDiscounts;
    }

    public static function unserialize(?array $data): self
    {
        $self = new self();

        if (!isset($data)) {
            return $self;
        }

        $data = Context::of($data);

        $self->afterTaxes = $data['afterTaxes']->map([T::class, 'bool']);
        $self->afterDiscounts = $data['afterDiscounts']->map([T::class, 'bool']);

        return $self;
    }
}