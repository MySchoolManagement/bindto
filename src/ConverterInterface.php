<?php
namespace Bindto;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Exception\ConversionException;

interface ConverterInterface
{

    /**
     * Convert the value.
     *
     * @param mixed $value
     * @param string $propertyName
     * @param array $options
     * @param mixed $from
     * @param array $metadata
     * @return mixed
     * @throws ConversionException When the conversion fails
     */
    public function apply($value, $propertyName, array $options, $from, array $metadata);

    /**
     * Can the converter output this type?
     *
     * @param string $type
     * @return bool
     */
    public function canProduceType(string $type): bool;

    /**
     * Does the converter support the given attribute.
     *
     * @param ConvertAttributeInterface $attribute
     * @return bool
     */
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool;

    /**
     * Where it is possible this is expected to create one or more automatically configured attribute that can then
     * be fed through the conversion system.
     *
     * @param ConvertAttributeInterface $sourceAttribute The attribute causing auto configuration
     * @param string                     $typeName
     * @param bool                       $isArray
     * @param bool                       $isNullable
     *
     * @return ConvertAttributeInterface[]
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array;
}
