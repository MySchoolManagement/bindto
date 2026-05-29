<?php
namespace Bindto\Attribute;

interface ConvertAttributeInterface
{
    public function isArray(): bool;
    public function getOptions(): array;
}
