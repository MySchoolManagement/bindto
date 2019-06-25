<?php
namespace Bindto\Annotation;

trait ConvertTrait
{
    /**
     * Is this a list of things that need converting?
     *
     * @var bool
     */
    public $isArray = false;

    public function isArray(): bool
    {
        return $this->isArray;
    }
}
