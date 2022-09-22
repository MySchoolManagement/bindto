<?php

declare(strict_types=1);

namespace Bindto\Mapper;

use Bindto\Annotation\AutoConvert;
use Bindto\Annotation\ConvertAnnotationInterface;
use Bindto\ConverterInterface;
use function Functional\first;
use Doctrine\Common\Collections\Collection;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Null_;
use ReflectionProperty;
use Ursula\Common\Exception\DomainException;

/**
 * Mapper that processes @AutoConvert annotations for a property.
 */
class AutoConverterProcessor
{
    /**
     * @var DocBlockFactoryInterface
     */
    private $docBlockFactory;

    /**
     * @var ContextFactory
     */
    private $docBlockContextFactory;

    /**
     * @var ConvertingObjectMapper
     */
    private $convertingObjectMapper;

    public function __construct(ConvertingObjectMapper $convertingObjectMapper)
    {
        $this->convertingObjectMapper = $convertingObjectMapper;
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->docBlockContextFactory = new ContextFactory();
    }

    public function process(ConvertAnnotationInterface $annotation, ReflectionProperty $property): array
    {
        $typeInfo = $this->extractTypeInformation($property);
        $converters = $this->convertingObjectMapper->getConverters();

        $converter = first($converters, function (ConverterInterface $converter) use ($typeInfo) {
            return $converter->canProduceType($typeInfo->typeName);
        });

        if (null === $converter) {
            throw new \DomainException('There is no converter that can produce this type: ' . $typeInfo->typeName);
        }

        return $converter->autoconfigure($annotation, $typeInfo->typeName, $typeInfo->isArray, $typeInfo->isNullable);
    }

    private function extractTypeInformation(ReflectionProperty $property): TypeInfo
    {
        $typeInfo = new TypeInfo();

        $docBlock = $this->docBlockFactory->create(
            $property->getDocComment(),
            $this->docBlockContextFactory->createFromReflector($property)
        );

        if (! $docBlock->hasTag('var')) {
            throw new DomainException(
                sprintf('Property "%s" on "%s" must define a type', $property->getName(), $property->getDeclaringClass())
            );
        }

        /** @var Var_ $var */
        $var = first($docBlock->getTagsByName('var'));
        $type = $var->getType();

        if ($type instanceof Compound) {
            $element1 = $type->get(0);
            $element2 = $type->get(1);
            $element3 = $type->get(2);

            if (((! $element1 instanceof Null_) && (! $element2 instanceof Null_))
                || (null !== $element3)) {
                throw new DomainException(
                    sprintf('Property "%s" on "%s" has complex compound type that is unsupported', $property->getName(), $property->getDeclaringClass())
                );
            }

            $type = $element1 instanceof Null_ ? $element2 : $element1;
            $typeInfo->isNullable = true;
        }

        if ($type instanceof Array_) {
            $typeInfo->isArray = true;
            $typeInfo->typeName = (string) $type->getValueType();
        } else {
            $typeInfo->typeName = (string) $type;

            if (class_exists($typeInfo->typeName) && in_array(Collection::class, class_implements($typeInfo->typeName))) {
                $typeInfo->isArray = true;
            }
        }

        if ('\\' === substr($typeInfo->typeName, 0, 1)) {
            $typeInfo->typeName = substr($typeInfo->typeName, 1);
        }

        return $typeInfo;
    }
}

class TypeInfo
{
    /**
     * @var string
     */
    public $typeName;

    /**
     * @var bool
     */
    public $isArray = false;

    /**
     * @var bool
     */
    public $isNullable = false;
}
