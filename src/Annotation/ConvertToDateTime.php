<?php
namespace Bindto\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class ConvertToDateTime extends AbstractConvert
{
    /**
     * Format to validate against.
     *
     * @var string
     */
    public $format = false;

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [
            'format' => $this->format,
        ];
    }
}
