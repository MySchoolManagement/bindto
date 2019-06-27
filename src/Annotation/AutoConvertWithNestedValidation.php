<?php
namespace Bindto\Annotation;

use Symfony\Component\Validator\Constraints\Valid;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class AutoConvertWithNestedValidation extends Valid implements ConvertAnnotationInterface
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
