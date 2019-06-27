<?php
namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Binder;
use Bindto\Converter\AbstractConverter;
use Bindto\Mapper\ConvertingObjectMapper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NestedObjectConverter extends AbstractConverter
{
    /**
     * @var ConvertingObjectMapper
     */
    private $parentMapper;

    /**
     * @param ConvertingObjectMapper $parentMapper
     */
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
        $prefix = $options['prefix'];

        if (is_object($from) === true) {
            $source = (array)$from;
        } else if (is_array($from) === true) {
            $source = $from;
        } else {
            throw new \InvalidArgumentException('$from must be a simple object or array');
        }

        if (true === array_key_exists($prefix, $source)) {
            $nestedSource = $source[$prefix];
        } else {
            $nestedSource = [];
        }

        $this->parentMapper->enterNestedExceptionStack($propertyName);
        {
            $result = $this->parentMapper->map($nestedSource, new $options['class'], $metadata);
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
            'prefix' => null,
        ]);
        $resolver->addAllowedTypes('class', ['string']);
        $resolver->addAllowedTypes('prefix', ['string']);
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function supportsAnnotation(ConvertAnnotationInterface $annotation): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        return [];
    }

}
