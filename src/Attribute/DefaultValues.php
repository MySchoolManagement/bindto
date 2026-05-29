<?php

namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DefaultValues
{
    public function __construct(
        /**
         * @var array<DefaultValue>
         */
        public readonly array $defaults
    )
    {
    }
}
