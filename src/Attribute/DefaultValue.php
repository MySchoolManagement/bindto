<?php

namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DefaultValue
{
    public function __construct(
        /**
         * A path can be provided for setting child properties.
         */
        public readonly ?string  $propertyPath = null,

        /**
         * Expression to apply.
         *
         * See http://symfony.com/doc/current/components/expression_language.html
         */
        public readonly ?string $expr = null,

        /**
         * Constant to use use, e.g. My\Class::SOME_CONSTANT
         */
        public readonly ?string $const = null,
    )
    {
    }
}
