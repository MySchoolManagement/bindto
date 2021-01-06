<?php
namespace Bindto\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class ConvertToString extends AbstractConvert
{
    /**
     * Should trimming of strings be disabled?
     *
     * @var bool
     */
    public $shouldDisableTrimming = false;

    /**
     * Pattern to apply against the value for validation.
     *
     * @var string
     */
    public $regex;

    /**
     * Constant where the pattern is stored to apply against the value for validation.
     *
     * @var string
     */
    public $regexConstant;

    /**
     * Constant where a translation key is stored to use when there is a conversion error.
     *
     * @var string
     */
    public $translationKeyConstant;

    /**
     * Identifier of the service to use for validation.
     *
     * @var string
     */
    public $validatorService;

    /**
     * Name of the method used for validation, defaults to 'validate'
     *
     * @var string
     */
    public $validatorMethod = 'validate';

    /**
     * Argument expressions for the service.
     *
     * @var string[]
     */
    public $validatorArguments = ['value'];

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
