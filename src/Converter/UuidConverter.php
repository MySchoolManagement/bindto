<?php
namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Annotation\ConvertToUuid;
use Bindto\Converter\AbstractConverter;
use Bindto\Exception\ConversionException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UuidConverter extends AbstractConverter
{

    /**
     * {@inheritdoc}
     */
    public function apply($value, $propertyPath, array $options, $from, array $metadata)
    {
        if (true === is_object($value)) {
            return $value;
        }

        try {
            return Uuid::fromString($value);
        } catch (\Throwable $ex) {
            throw ConversionException::fromDomain($propertyPath, $value, $ex->getMessage(), 'conversion_exception.invalid_argument_exception',$ex);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'version' => 4,
        ]);
        $resolver->addAllowedTypes('format', ['int']);
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return UuidInterface::class === $type || class_exists($type) && in_array(UuidInterface::class, class_implements($type));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAnnotation(ConvertAnnotationInterface $annotation): bool
    {
        return $annotation instanceof ConvertToUuid;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToUuid();
        $annotation->isArray;

        return [$annotation];
    }
}
