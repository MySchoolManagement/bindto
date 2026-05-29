<?php
namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Converters
{
    public function __construct(
        /**
         * @var array<Convert>
         */
        public readonly array $converters
    )
    {}
}
