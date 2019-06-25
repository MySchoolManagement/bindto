<?php
namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Annotation\ConvertToBool;
use Bindto\Exception\ConversionException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    public function supportsAnnotation(ConvertAnnotationInterface $annotation): bool
    {
        return $annotation instanceof ConvertToBool;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToBool();
        $annotation->isArray = $isArray;

        return [$annotation];
    }
}
