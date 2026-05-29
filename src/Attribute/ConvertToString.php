<?php
namespace Bindto\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class ConvertToString extends AbstractConvert
{
    public function __construct(
        /**
         * Pattern to apply against the value for validation.
         */
        public readonly ?string $regex = null,

        /**
         * Constant where the pattern is stored to apply against the value for validation.
         */
        public readonly ?string $regexConstant = null,

        /**
         * Constant where a translation key is stored to use when there is a conversion error.
         */
        public readonly ?string $translationKeyConstant = null,

        /**
         * Identifier of the service to use for validation.
         */
        public readonly ?string $validatorService = null,

        /**
         * Name of the method used for validation.
         */
        public readonly string $validatorMethod = 'validate',

        /**
         * Argument expressions for the service.
         *
         * @var array<string>
         */
        public readonly array $validatorArguments = ['value'],

        /**
         * Should trimming of strings be disabled?
         */
        public readonly bool $shouldDisableTrimming = false,

        bool $isArray = false,
    )
    {
        parent::__construct($isArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [
            'disableTrimming' => $this->shouldDisableTrimming,
            'regex' => $this->regex,
            'regexConstant' => $this->regexConstant,
            'translationKeyConstant' => $this->translationKeyConstant,
            'validatorService' => $this->validatorService,
            'validatorMethod' => $this->validatorMethod,
            'validatorArguments' => $this->validatorArguments
        ];
    }
}
