<?php
namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Annotation\ConvertToArray;
use Bindto\Exception\ConversionException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    public function supportsAnnotation(ConvertAnnotationInterface $annotation): bool
    {
        return $annotation instanceof ConvertToArray;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToArray();
        $annotation->isArray = $isArray;

        return [$annotation];
    }

}
