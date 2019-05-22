<?php

namespace Bindto;

interface MapperInterface
{
    /**
     * Map all the properties from $from to $to, returning a new object.
     *
     * @param $from
     * @param $to
     * @param $metadata
     *
     * @return mixed
     */
    public function map($from, $to, array $metadata);
}
