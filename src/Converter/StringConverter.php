<?php
namespace Bindto\Converter;

use Bindto\Exception\ConversionException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StringConverter extends AbstractPrimitiveConverter
{
    /**
     * {@inheritdoc}
     */
    public function onApply($value, $propertyName, array $options, $from)
    {
        if($value !== null){
           $value = (string) $value;
        }

        if(!$options['disableTrimming']){
            $value = trim($value);
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
        return ConversionException::fromDomain($propertyName, $value, 'Not a valid string', 'conversion_exception.primitive.string.not_a_valid_type');
    }


    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('disableTrimming', false);
        $resolver->addAllowedTypes('disableTrimming', ['bool']);
    }
}
