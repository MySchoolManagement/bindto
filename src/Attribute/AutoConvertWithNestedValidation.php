<?php
namespace Bindto\Attribute;

use Attribute;
use Symfony\Component\Validator\Constraints\Valid;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class AutoConvertWithNestedValidation extends Valid implements ConvertAttributeInterface
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
