<?php
namespace Bindto\Converter;

use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Annotation\ConvertToString;
use Bindto\Exception\ConversionException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StringConverter extends AbstractPrimitiveConverter
{
    const DEFAULT_TRANSLATION_KEY = 'conversion_exception.primitive.string.not_a_valid_type';

    /**
     * {@inheritdoc}
     */
    public function onApply($value, $propertyName, array $options, $from)
    {
        if($value !== null){
           $value = (string) $value;
        }

        if(! $options['disableTrimming']){
            $value = trim($value);
        }

        $regexPattern = $options['regex'];

        if (mb_strlen($options['regexConstant']) > 0) {
            $regexPattern = constant($options['regexConstant']);
        }

        if (mb_strlen($regexPattern) > 0 && preg_match($regexPattern, $value) == 0) {
            if (mb_strlen($options['translationKeyConstant']) > 0) {
                $translationKey = constant($options['translationKeyConstant']);
            } else {
                $translationKey = self::DEFAULT_TRANSLATION_KEY;
            }

            throw ConversionException::fromDomain($propertyName, $value, 'Conversion failed', $translationKey);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function needsConverting($value)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidTypeException(string $propertyName, $value)
    {
        return ConversionException::fromDomain($propertyName, $value, 'Not a valid string', self::DEFAULT_TRANSLATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('disableTrimming', false);
        $resolver->setDefault('regex', null);
        $resolver->setDefault('regexConstant', null);
        $resolver->setDefault('translationKeyConstant', null);

        $resolver->addAllowedTypes('disableTrimming', ['bool']);
        $resolver->addAllowedTypes('regex', ['null', 'string']);
        $resolver->addAllowedTypes('regexConstant', ['null', 'string']);
        $resolver->addAllowedTypes('translationKeyConstant', ['null', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return 'string' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAnnotation(ConvertAnnotationInterface $annotation): bool
    {
        return $annotation instanceof ConvertToString;
    }

    /**
     * {@inheritdoc}
     */
    public function autoconfigure(ConvertAnnotationInterface $sourceAnnotation, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToString();
        $annotation->isArray = $isArray;

        return [$annotation];
    }
}
