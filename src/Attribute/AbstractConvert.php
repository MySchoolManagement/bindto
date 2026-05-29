<?php
namespace Bindto\Attribute;

abstract class AbstractConvert implements ConvertAttributeInterface
{
    public function __construct(private bool $isArray = false)
    {
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}
