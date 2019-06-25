<?php
namespace Bindto\Annotation;

use Symfony\Component\Validator\Constraints\Valid;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
abstract class AbstractValidConvert extends Valid implements ConvertAnnotationInterface
{
    use ConvertTrait;

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        // TODO: Implement getOptions() method.
    }
}
