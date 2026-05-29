<?php
namespace Bindto\Converter;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\ConvertToUuid;
use Bindto\Exception\ConversionException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UuidConverter extends AbstractConverter
{

    /**
     * {@inheritdoc}
     */
    public function apply($value, $propertyName, array $options, $from, array $metadata)
    {
        if (true === is_object($value)) {
            return $value;
        }

        try {
            return Uuid::fromString($value);
        } catch (\Throwable $ex) {
            throw ConversionException::fromDomain($propertyName, $value, $ex->getMessage(), 'conversion_exception.invalid_argument_exception',$ex);
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
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool
    {
        return $attribute instanceof ConvertToUuid;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToUuid($isArray);

        return [$annotation];
    }
}
