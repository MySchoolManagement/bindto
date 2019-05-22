<?php

namespace Bindto\Mapper;

use Bindto\MapperInterface;
use Bindto\PSR7RequestTrait;

class ServerRequestPSR7Mapper implements MapperInterface
{
    use PSR7RequestTrait;

    public function map($from, $to, array $metadata)
    {
        $copyOfTo = $to;
        $this->fillPropertiesFromPSR7Request($from, $copyOfTo);

        return $copyOfTo;
    }
}
