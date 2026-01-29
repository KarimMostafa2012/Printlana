<?php declare(strict_types=1);

namespace Aikinomi\Wbsng\Model\Config\Document;


use Aikinomi\Wbsng\Mapping\Context;
use Aikinomi\Wbsng\Mapping\T;


class Settings
{
    public $disableSplitShipping;

    public function __construct(bool $disableSplitShipping = false)
    {
        $this->disableSplitShipping = $disableSplitShipping;
    }

    public static function unserialize(?array $data): self
    {
        $self = new self();

        if (!isset($data)) {
            return $self;
        }

        $data = Context::of($data);

        $self->disableSplitShipping = $data['disableSplitShipping']->map([T::class, 'bool']);

        return $self;
    }
}