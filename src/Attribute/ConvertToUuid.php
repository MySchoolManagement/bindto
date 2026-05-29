<?php

namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class ConvertToUuid extends AbstractConvert
{
    public function __construct(public readonly int $version, bool $isArray = false)
    {
        parent::__construct($isArray);
    }

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
