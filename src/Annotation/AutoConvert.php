<?php
namespace Bindto\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class AutoConvert implements ConvertAnnotationInterface
{
    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}
