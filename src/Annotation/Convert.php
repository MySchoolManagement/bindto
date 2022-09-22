<?php
namespace Bindto\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class Convert implements ConvertAnnotationInterface
{

    /**
     * Name of the converter to apply.
     *
     * @var string
     * @Required()
     */
    public $converter;

    /**
     * Is this a list of things that need converting?
     *
     * @var bool
     */
    public $isArray = false;

    /**
     * Options to pass to the converter.
     *
     * @var array
     */
    public $options = [];

    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
