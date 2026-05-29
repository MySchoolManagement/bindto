<?php
namespace Bindto\Mapper;

use Bindto\Attribute\DefaultValue;
use Bindto\MapperInterface;

/**
 * Mapper that reads @DefaultValue annotations and attempts to set a default value.
 */
class DefaultValueMapper implements MapperInterface
{
    private DefaultValueProcessor $defaultValueProcessor;

    /**
     * @param DefaultValueProcessor $defaultValueProcessor
     */
    public function __construct(DefaultValueProcessor $defaultValueProcessor)
    {
        $this->defaultValueProcessor = $defaultValueProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function map($from, $to, array $metadata)
    {
        if (is_object($to)) {
            $reflector = new \ReflectionClass($to);

            array_map(function (\ReflectionProperty $property) use ($from, $to) {
                $this->defaultValueProcessor->process($property, $to);
            }, $reflector->getProperties());
        }

        return $to;
    }
}
