<?php declare(strict_types=1);

namespace Aikinomi\Wbsng\Mapping\Exceptions;


class InvalidType extends Invalid
{
    public function __construct(string $expectedType, string $actualType)
    {
        parent::__construct("expected $expectedType, got $actualType");
    }
}