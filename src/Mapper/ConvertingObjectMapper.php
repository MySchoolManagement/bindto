<?php

namespace Bindto\Mapper;

use Bindto\Attribute\AutoConvert;
use Bindto\Attribute\AutoConvertWithNestedValidation;
use Bindto\Attribute\Convert;
use Bindto\Attribute\ConvertAttributeInterface;
use Bindto\Attribute\Converters;
use Bindto\Converter\NestedObjectConverter;
use Bindto\ConverterInterface;
use Bindto\Exception\ConversionException;
use Bindto\MapperInterface;
use ReflectionAttribute;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Ursula\Common\Exception\DomainException;
use function Functional\each;
use function Functional\filter;
use function Functional\first;
use function Functional\map;

/**
 * Mapper that reads @Convert annotations and attempts to convert the value.
 *
 * To function this requires another mapper to do the initial binding then this operates on the result.
 */
class ConvertingObjectMapper implements MapperInterface
{
    const STACK_TEMPLATE = [
        'children' => [],
        'exceptions' => [],
        'parent' => null,
    ];

    /**
     * @var MapperInterface
     */
    private $propertyMapper;

    /**
     * @var PropertyAccess
     */
    private $propertyAccessor;

    /**
     * @var DefaultValueProcessor
     */
    private $defaultValueProcessor;

    /**
     * @var AutoConverterProcessor
     */
    private $autoConverterProcessor;

    /**
     * @var array
     */
    private $converters = [];

    /**
     * @var array
     */
    private $exceptionStack = null;

    /**
     * @var array Reference to a position in $exceptionStack
     */
    private $currentExceptionStackPointer = null;

    /**
     * @var bool
     */
    private $collectExceptions;

    /**
     * Is the conversion phase enabled?
     *
     * @var bool
     */
    private $enabled = true;

    public function __construct(MapperInterface $propertyMapper, DefaultValueProcessor $defaultValueProcessor, bool $collectExceptions = false)
    {
        $this->propertyMapper = $propertyMapper;
        $this->defaultValueProcessor = $defaultValueProcessor;
        $this->autoConverterProcessor = new AutoConverterProcessor($this);
        $this->collectExceptions = $collectExceptions;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->clearNestedExceptionStack();
    }

    public function addConverter(string $name, ConverterInterface $converter)
    {
        if (array_key_exists($name, $this->converters)) {
            throw new \LogicException(
                sprintf('"%s" is already registered for another converter', $name)
            );
        }

        $this->converters[$name] = $converter;
    }

    /**
     * @return ConverterInterface[]
     */
    public function getConverters(): array
    {
        return $this->converters;
    }

    /**
     * Disables the conversion phase.
     *
     * Nested converters will still run to create the correct object structure.
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables the conversion phase. Enabled by default.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    public function map($from, $to, array $metadata)
    {
        $this->propertyMapper->map($from, $to, $metadata);

        if (is_object($to)) {
            $reflector = new \ReflectionClass($to);

            each($reflector->getProperties(), function (\ReflectionProperty $property) use ($from, $to, $metadata) {
                $expectedToBeAutoConfigured = false;
                $reflAttributes = $property->getAttributes();

                $attributes = map($reflAttributes, function (ReflectionAttribute $reflAttribute) use ($property, $from, $to, $metadata, &$expectedToBeAutoConfigured) {
                    $attribute = $reflAttribute->newInstance();
                    $attributes = [];

                    if ($attribute instanceof AutoConvert || $attribute instanceof AutoConvertWithNestedValidation) {
                        $attributes = array_merge(
                            $attributes,
                            $this->autoConverterProcessor->process($attribute, $property)
                        );

                        $expectedToBeAutoConfigured = true;
                    } elseif ($attribute instanceof Converters) {
                        $attributes = $attribute->converters;
                    } elseif ($attribute instanceof ConvertAttributeInterface) {
                        $attributes[] = $attribute;
                    }

                    return $attributes;
                });

                // FIXME: this cannot currently be enabled because some transfer objects inherit from parent that exposes
                // properties but have no converters (e.g. DomainMessage)
                /*if (empty($convertAnnotations)) {
                    throw new DomainException(
                        sprintf('Binding "%s::%s" but it has no conversion annotations', $property->getDeclaringClass()->getName(), $property->getName())
                    );
                }*/

                if (empty($attributes)) {
                    return;
                }

                $attributes = array_merge(...$attributes);

                if ($expectedToBeAutoConfigured && empty($attributes)) {
                    throw new DomainException(
                        sprintf('Binding "%s::%s" was expected to be auto-configured but resulted in no conversion attributes', $property->getDeclaringClass()->getName(), $property->getName())
                    );
                }

                if ($this->enabled && ! empty($attributes)) {
                    $this->defaultValueProcessor->process($property, $to);
                }

                each($attributes, function ($attribute) use ($metadata, $to, $from, $property) {
                    $this->processProperty($attribute, $property, $from, $to, $metadata + ['parent' => $to]);
                });
            });
        }

        return $to;
    }

    private function processProperty(ConvertAttributeInterface $attribute, \ReflectionProperty $property, $source, $obj, array $metadata)
    {
        $propertyName = $property->getName();
        $value = $this->getPropertyValue($obj, $propertyName);

        if ($attribute->isArray()) {
            if (null === $value) {
                return;
            }

            foreach ($value as $key => $item) {
                $filteredItem = $this->filterNestedObjects($item);
                $propertyPath = sprintf('%s[%s]', $property->getName(), $key);
                $convertedItem = null;

                if (null !== $filteredItem) {
                    $convertedItem = $this->convert($filteredItem, $propertyPath, $attribute, $obj, $metadata);
                }

                $this->setPropertyValue($obj, $propertyPath, $convertedItem);
            }
        } else {
            $filteredValue = $this->filterNestedObjects($value);
            $convertedValue = null;

            if (null !== $filteredValue) {
                $convertedValue = $this->convert($filteredValue, $propertyName, $attribute, $obj, $metadata);
            }

            $this->setPropertyValue($obj, $propertyName, $convertedValue);
        }
    }

    /**
     * Pushes a new conversion exception stack on.
     *
     * @param string $propertyName
     */
    public function enterNestedExceptionStack($propertyName)
    {
        $this->currentExceptionStackPointer['children'][$propertyName] = self::STACK_TEMPLATE;
        $this->currentExceptionStackPointer['children'][$propertyName]['parent'] = &$this->currentExceptionStackPointer;
        $this->currentExceptionStackPointer = &$this->currentExceptionStackPointer['children'][$propertyName];
    }

    public function exitNestedExceptionStack()
    {
        if (null !== $this->currentExceptionStackPointer['parent']) {
            $this->currentExceptionStackPointer = &$this->currentExceptionStackPointer['parent'];
        }
    }

    public function clearNestedExceptionStack()
    {
        $this->exceptionStack = static::STACK_TEMPLATE;
        $this->currentExceptionStackPointer = &$this->exceptionStack;
    }

    public function flattenNestedExceptionStack(): array
    {
        return $this->flattenNestedExceptionStackRecursive($this->exceptionStack);
    }

    protected function flattenNestedExceptionStackRecursive(array $level, array $propertyPath = [])
    {
        $flattened = [];

        foreach ($level['exceptions'] as $exception) {
            if ($exception instanceof ConversionException) {
                $exception->setPropertyPath(
                    join('.', array_merge($propertyPath, [$exception->getPropertyPath()]))
                );
            }

            $flattened[] = $exception;
        }

        foreach ($level['children'] as $propertyName => $childStack) {
            $flattened = array_merge($flattened,
                $this->flattenNestedExceptionStackRecursive($childStack, array_merge($propertyPath, [$propertyName]))
            );
        }

        return $flattened;
    }

    protected function convert($value, $propertyPath, ConvertAttributeInterface $attribute, $from, array $metadata)
    {
        if ($attribute instanceof Convert) {
            if (! array_key_exists($attribute->converter, $this->converters)) {
                throw new \LogicException(
                    sprintf('Converter with the name "%s" could not be found', $attribute->converter)
                );
            }

            $converter = $this->converters[$attribute->converter];
        } else {
            $converter = $this->getConverterFromAttribute($attribute);
        }

        $isNestedConverter = $converter instanceof NestedObjectConverter;

        // when converters are disabled we only want to run the nested converters so that we create the correct object structure
        if ((false === $this->enabled) && (false === $isNestedConverter)) {
            return $value;
        }

        if ((null === $value) && (false === $isNestedConverter)) {
            return null;
        }

        try {
            return $converter->apply($value, $propertyPath, $attribute->getOptions(), $from, $metadata);
        } catch (ConversionException $ex) {
            if ($this->collectExceptions === true) {
                $this->currentExceptionStackPointer['exceptions'][] = $ex;
            } else {
                throw $ex;
            }
        }

        return null;
    }

    /**
     * @param ConvertAttributeInterface $attribute
     * @return ConverterInterface
     *
     * @throws \LogicException When the converter does not exist
     */
    protected function getConverterFromAttribute(ConvertAttributeInterface $attribute)
    {
        $converter = first($this->converters, function (ConverterInterface $converter) use ($attribute) {
            return $converter->supportsAttribute($attribute);
        });

        if (null === $converter) {
            throw new \LogicException('Could not find converter for: ' . get_class($attribute));
        }

        return $converter;
    }

    protected function getPropertyValue($obj, $propertyPath)
    {
        return $this->propertyAccessor->getValue($obj, $propertyPath);
    }

    protected function setPropertyValue($obj, $propertyPath, $value)
    {
        $this->propertyAccessor->setValue($obj, $propertyPath, $value);
    }

    /**
     * Some values cause problems with nested objects, filter those out.
     *
     * Example:
     *
     *      [
     *          key1 => value1,
     *          key2 => null,
     *          nested1 => [
     *              key1 => null
     *          ]
     *      ]
     *
     * Where nested1 is marked as a nested object but IS allowed to be null and where nested1.key1 is NOT allowed to be
     * null. Without filtering nested1.key1 out and setting nested1 to null, we will try to convert this and it will
     * fail validation because nested1 will become an instance of the target class but with key1 being null.
     *
     * @param mixed $item
     * @return mixed
     */
    private function filterNestedObjects($item)
    {
        if ((false === $this->enabled) || (false === is_array($item))) {
            return $item;
        }

        // filter null values to satisfy the case where a nested object is optional
        $itemSize = count($item);
        $filtered = filter($item, function($e) {
            return !is_null($e);
        });
        $newSize = count($filtered);

        // if $item is empty because of our filter, set it to null also
        if (($newSize !== $itemSize) && ($newSize === 0)) {
            return null;
        }

        return $filtered;
    }
}
