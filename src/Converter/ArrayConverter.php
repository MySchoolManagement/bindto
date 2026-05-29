<?php
namespace Bindto\Converter;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\ConvertToArray;
use Bindto\Exception\ConversionException;

class ArrayConverter extends AbstractPrimitiveConverter
{

    /**
     * {@inheritdoc}
     */
    public function onApply($value, $propertyName, array $options, $from)
    {
        return (array) $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function needsConverting($value)
    {
        return !is_array($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidTypeException(string $propertyName, $value)
    {
        return ConversionException::fromDomain($propertyName, $value, 'Not a valid array', 'conversion_exception.primitive.array.not_a_valid_type');
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool
    {
        return $attribute instanceof ConvertToArray;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToArray($isArray);

        return [$annotation];
    }

}
