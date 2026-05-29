<?php
namespace Bindto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class ConvertToDateTime extends AbstractConvert
{
    public function __construct(
        public readonly string $format,
        bool $isArray = false
    ) {
        parent::__construct($isArray);
    }

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
