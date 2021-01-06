<?php

declare(strict_types=1);

namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Mapper\ConvertingObjectMapper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NestedObjectConverter extends AbstractConverter
{
    /**
     * @var ConvertingObjectMapper
     */
    private $parentMapper;

    public function __construct(ConvertingObjectMapper $parentMapper)
    {
        $this->parentMapper = $parentMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($value, $propertyName, array $options, $from, array $metadata)
    {
        $options = $this->resolveOptions($options);

        $this->parentMapper->enterNestedExceptionStack($propertyName);
        {
            $result = $this->parentMapper->map($value, new $options['class'](), $metadata);
        }
        $this->parentMapper->exitNestedExceptionStack();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => null,
        ]);
        $resolver->addAllowedTypes('class', ['string']);
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        return [];
    }
}
