<?php
namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Convert implements ConvertAttributeInterface
{
    public function __construct(
        public readonly string $converter,
        public readonly array $options = [],
        public readonly bool $isArray = false,
    )
    {}

    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
