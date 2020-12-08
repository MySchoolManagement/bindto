<?php

declare(strict_types=1);

namespace Bindto\Exception;

class MultipleConversionException extends \Exception implements ExceptionInterface
{
    /**
     * @var ConversionException[]
     */
    private $conversionExceptions;

    /**
     * @param ConversionException[] $conversionExceptions
     */
    public function __construct(array $conversionExceptions)
    {
        $this->conversionExceptions = $conversionExceptions;

        parent::__construct('There are many conversion exceptions');
    }

    /**
     * @return ConversionException[]
     */
    public function getConversionExceptions(): array
    {
        return $this->conversionExceptions;
    }
}
