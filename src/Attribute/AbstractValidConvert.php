<?php
namespace Bindto\Attribute;

use Symfony\Component\Validator\Constraints\Valid;

abstract class AbstractValidConvert extends Valid implements ConvertAttributeInterface
{
    public function __construct(private readonly bool $isArray = false)
    {
        parent::__construct();
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}
