<?php
namespace Bindto\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
abstract class AbstractConvert implements ConvertAnnotationInterface
{
    use ConvertTrait;

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}
