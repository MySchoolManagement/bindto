<?php
namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class AutoConvert implements ConvertAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}
