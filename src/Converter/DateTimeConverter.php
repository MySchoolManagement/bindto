<?php
namespace Bindto\Converter;

use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\ConvertToDateTime;
use Bindto\Exception\ConversionException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ursula\EntityFramework\ValueObject\DateTime;

class DateTimeConverter extends AbstractConverter
{

    /**
     * {@inheritdoc}
     */
    public function apply($value, $propertyName, array $options, $from, array $metadata)
    {
        $options = $this->resolveOptions($options);
        $date = null;

        if ($options['format']) {
            $date = \DateTime::createFromFormat($options['format'], $value);

            if ($date === false) {
                throw ConversionException::fromDomain($propertyName, $value, 'Invalid format', 'conversion_exception.date_time.invalid_format');
            }
        } else {
            try {
                $date = new \DateTime($value);
            } catch (\Exception $ex) {
                throw ConversionException::fromDomain($propertyName, $value, $ex->getMessage(), 'conversion_exception.date_time.generic_exception');
            }
        }

        if (null !== $date) {
            return DateTime::fromNative($date);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'format' => null,
        ]);
        $resolver->addAllowedTypes('format', ['null', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function canProduceType(string $type): bool
    {
        return DateTime::class === $type;
    }

    /**
     * @inheritDoc
     */
    public function supportsAttribute(ConvertAttributeInterface $attribute): bool
    {
        return $attribute instanceof ConvertToDateTime;
    }

    /**
     * @inheritDoc
     */
    public function autoconfigure(ConvertAttributeInterface $sourceAttribute, string $typeName, bool $isArray, bool $isNullable): array
    {
        $annotation = new ConvertToDateTime(
            format: DateTime::DEFAULT_FORMAT,
            isArray: $isArray,
        );

        return [$annotation];
    }

}
