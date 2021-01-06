<?php

namespace Bindto\Mapper;

use Assert\Assertion;
use Bindto\Annotation\AutoConvert;
use Bindto\Annotation\AutoConvertWithNestedValidation;
use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\Annotation\Converters;
use Bindto\Converter\NestedObjectConverter;
use Bindto\ConverterInterface;
use Bindto\Exception\ConversionException;
use Doctrine\Common\Annotations\Reader;
use Bindto\Annotation\Convert;
use Bindto\MapperInterface;
use function Functional\filter;
use function Functional\first;
use function Functional\each;
use function Functional\map;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Ursula\Common\Exception\DomainException;

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
     * @var Reader
     */
    private $annotationReader;

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

    public function __construct(MapperInterface $propertyMapper, Reader $annotationReader, DefaultValueProcessor $defaultValueProcessor, bool $collectExceptions = false)
    {
        $this->propertyMapper = $propertyMapper;
        $this->annotationReader = $annotationReader;
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
                $propertyAnnotations = $this->annotationReader->getPropertyAnnotations($property);

                $convertAnnotations = map($propertyAnnotations, function ($annotation) use ($property, $from, $to, $metadata, &$expectedToBeAutoConfigured) {
                    $convertAnnotations = [];

                    if ($annotation instanceof AutoConvert || $annotation instanceof AutoConvertWithNestedValidation) {
                        $convertAnnotations = array_merge(
                            $convertAnnotations,
                            $this->autoConverterProcessor->process($annotation, $property)
                        );

                        $expectedToBeAutoConfigured = true;
                    } elseif ($annotation instanceof Converters) {
                        $convertAnnotations = $annotation->converters;
                    } elseif ($annotation instanceof ConvertAnnotationInterface) {
                        $convertAnnotations[] = $annotation;
                    }

                    return $convertAnnotations;
                });

                // FIXME: this cannot currently be enabled because some transfer objects inherit from parent that exposes
                // properties but have no converters (e.g. DomainMessage)
                /*if (empty($convertAnnotations)) {
                    throw new DomainException(
                        sprintf('Binding "%s::%s" but it has no conversion annotations', $property->getDeclaringClass()->getName(), $property->getName())
                    );
                }*/

                if (empty($convertAnnotations)) {
                    return;
                }

                $convertAnnotations = array_merge(...$convertAnnotations);

                if ($expectedToBeAutoConfigured && empty($convertAnnotations)) {
                    throw new DomainException(
                        sprintf('Binding "%s::%s" was expected to be auto-configured but resulted in no conversion annotations', $property->getDeclaringClass()->getName(), $property->getName())
                    );
                }

                if ($this->enabled && ! empty($convertAnnotations)) {
                    $this->defaultValueProcessor->process($property, $to);
                }

                each($convertAnnotations, function ($annotation) use ($metadata, $to, $from, $property) {
                    $this->processProperty($annotation, $property, $from, $to, $metadata);
                });
            });
        }

        return $to;
    }

    private function processProperty(ConvertAnnotationInterface $annotation, \ReflectionProperty $property, $source, $obj, array $metadata)
    {
        $propertyName = $property->getName();
        $value = $this->getPropertyValue($obj, $propertyName);

        if ($annotation->isArray()) {
            if (null === $value) {
                return;
            }

            foreach ($value as $key => $item) {
                $filteredItem = $this->filterNestedObjects($item);
                $propertyPath = sprintf('%s[%s]', $property->getName(), $key);
                $convertedItem = null;

                if (null !== $filteredItem) {
                    $convertedItem = $this->convert($filteredItem, $propertyPath, $annotation, $obj, $metadata);
                }

                $this->setPropertyValue($obj, $propertyPath, $convertedItem);
            }
        } else {
            $filteredValue = $this->filterNestedObjects($value);
            $convertedValue = null;

            if (null !== $filteredValue) {
                $convertedValue = $this->convert($filteredValue, $propertyName, $annotation, $obj, $metadata);
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

    private function flattenNestedExceptionStackRecursive(array $level, array $propertyPath = [])
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

    protected function convert($value, $propertyPath, ConvertAnnotationInterface $annotation, $from, array $metadata)
    {
        if ($annotation instanceof Convert) {
            if (! array_key_exists($annotation->converter, $this->converters)) {
                throw new \LogicException(
                    sprintf('Converter with the name "%s" could not be found', $annotation->converter)
                );
            }

            $converter = $this->converters[$annotation->converter];
        } else {
            $converter = $this->getConverterFromAnnotation($annotation);
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
            return $converter->apply($value, $propertyPath, $annotation->getOptions(), $from, $metadata);
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
     * @param ConvertAnnotationInterface $annotation
     * @return ConverterInterface
     *
     * @throws \LogicException When the converter does not exist
     */
    protected function getConverterFromAnnotation(ConvertAnnotationInterface $annotation)
    {
        $converter = first($this->converters, function (ConverterInterface $converter) use ($annotation) {
            return $converter->supportsAnnotation($annotation);
        });

        if (null === $converter) {
            throw new \LogicException('Could not find converter for: ' . get_class($annotation));
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
