<?php
namespace Bindto\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class ConvertToUuid extends AbstractConvert
{
    /**
     * Which version of UUID is expected?
     *
     * @var int
     */
    public $version = 4;

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [
            'version' => $this->version,
        ];
    }
}
