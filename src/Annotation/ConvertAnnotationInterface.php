<?php
namespace Bindto\Annotation;

interface ConvertAnnotationInterface
{
    public function isArray(): bool;
    public function getOptions(): array;
}
