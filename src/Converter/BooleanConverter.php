<?php
namespace Bindto\Converter;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\ConvertToBool;
use Bindto\Exception\ConversionException;

class BooleanConverter extends AbstractPrimitiveConverter
{

    /**
     * {@inheritdoc}
     */
    public function onApply($value, $propertyName, array $options, $from)
    {
        if(!is_bool($value)){
            throw $this->createInvalidTypeException($propertyName, $value);
        }
        return (bool) $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function needsConverting($value)
    {
        return !is_bool($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidTypeException(string $propertyName, $value)
    {
        return ConversionException::fromDomain($propertyName, $value, 'Not a valid boolean', 'conversion_exception.primitive.boolean.not_a_valid_type');
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return 'bool' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool
    {
        return $attribute instanceof ConvertToBool;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToBool($isArray);

        return [$annotation];
    }
}
