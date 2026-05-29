<?php
namespace Bindto\Converter;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\ConvertToInt;
use Bindto\Exception\ConversionException;

class IntegerConverter extends AbstractPrimitiveConverter
{

    /**
     * {@inheritdoc}
     */
    public function onApply($value, $propertyName, array $options, $from)
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function needsConverting($value)
    {
        return !is_int($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidTypeException(string $propertyName, $value)
    {
        return ConversionException::fromDomain($propertyName, $value, 'Not a valid integer', 'conversion_exception.primitive.integer.not_a_valid_type');
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return 'int' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool
    {
        return $attribute instanceof ConvertToInt;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToInt($isArray);

        return [$annotation];
    }
}
