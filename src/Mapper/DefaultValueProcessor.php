<?php

declare(strict_types=1);

namespace Bindto\Mapper;

use Bindto\Attribute\DefaultValue;
use Bindto\Attribute\DefaultValues;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function Functional\each;
use function Functional\map;

/**
 * Mapper that processes @DefaultValue annotations for a property.
 */
class DefaultValueProcessor
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var PropertyAccess
     */
    private $propertyAccessor;

    /**
     * Track which defaults have been applied to objects.
     *
     * @var bool[]
     */
    private $processedMap = [];

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param \ReflectionProperty $property
     * @param                     $obj
     */
    public function process(\ReflectionProperty $property, $obj)
    {
        $attributes = map($property->getAttributes(), fn(\ReflectionAttribute $attrib) => $attrib->newInstance());

        each($attributes,
            function ($attribute) use ($property, $obj) {
                $valueAttributes = [];

                if ($attribute instanceof DefaultValues) {
                    $valueAttributes = $attribute->defaults;
                } else if ($attribute instanceof DefaultValue) {
                    $valueAttributes[] = $attribute;
                }

                foreach ($valueAttributes as $valueAttribute) {
                    if (false === $this->hasProcessed($valueAttribute, $obj, $property)) {
                        $this->processProperty($valueAttribute, $property, $obj);
                    }
                }
            }
        );
    }

    private function processProperty(DefaultValue $attribute, \ReflectionProperty $property, $obj)
    {
        $propertyName = $property->getName();
        $value = $this->getPropertyValue($obj, $propertyName, $attribute->propertyPath);

        // if there's already a value do nothing
        if (null === $value) {
            if (null !== $attribute->expr) {
                $newValue = $this->evaluateExpression($attribute->expr, $obj);
            } elseif (null !== $attribute->const) {
                $newValue = $this->evaluateConstant($attribute->const);
            } else {
                throw new \LogicException('No default value mechanism provided');
            }

            $this->setPropertyValue($obj, $propertyName, $attribute->propertyPath, $newValue);
            $this->setProcessed($attribute, $obj, $property);
        }
    }

    private function hasProcessed(DefaultValue $attribute, $obj, \ReflectionProperty $property): bool
    {
        return in_array($this->createCacheKey($attribute, $obj, $property), $this->processedMap, true);
    }

    private function setProcessed(DefaultValue $attribute, $obj, \ReflectionProperty $property)
    {
        $this->processedMap[] = $this->createCacheKey($attribute, $obj, $property);
    }

    private function createCacheKey(DefaultValue $attribute, $obj, \ReflectionProperty $property): string
    {
        return implode('', [
            spl_object_hash($attribute),
            spl_object_hash($obj),
            spl_object_hash($property),
        ]);
    }

    private function getPropertyValue($obj, string $rootProperty, string $childPropertyPath = null)
    {
        $propertyPath = $rootProperty;

        if (null !== $childPropertyPath) {
            $propertyPath = sprintf('%s[%s]', $propertyPath, $childPropertyPath);
        }

        try {
            return $this->propertyAccessor->getValue($obj, $propertyPath);
        } catch (UnexpectedTypeException $ex) {
            // this can happen when one or more parts of the property path is not an object or array
            return null;
        } catch (NoSuchIndexException $ex) {
            // this can happen when one or more parts of the property path is an object without public properties
            return null;
        }
    }

    private function setPropertyValue($obj, string $rootProperty, string $childPropertyPath = null, $value)
    {
        $propertyPath = $rootProperty;

        if (null !== $childPropertyPath) {
            $propertyPath = sprintf('%s[%s]', $propertyPath, $childPropertyPath);
        }

        // the root property itself can be null
        if (null === $this->getPropertyValue($obj, $rootProperty)) {
            $this->propertyAccessor->setValue($obj, $rootProperty, []);
        }

        try {
            $this->propertyAccessor->setValue($obj, $propertyPath, $value);
        } catch (NoSuchIndexException $ex) {
            // this can happen when one or more parts of the property path is an object without public properties
            return null;
        }
    }

    private function evaluateExpression(string $expr, $obj)
    {
        $exprValue = $this->expressionLanguage->evaluate($expr, [
            'this' => $obj,
        ]);

        return $exprValue;
    }

    private function evaluateConstant(string $constant)
    {
        return constant($constant);
    }
}
