<?php

namespace Unleash\Client\Exception;

use Exception;
use Throwable;

final class CompoundException extends Exception
{
    /**
     * @var Throwable[]
     * @readonly
     */
    private $exceptions;

    public function __construct(Throwable ...$exceptions)
    {
        $this->exceptions = $exceptions;
        parent::__construct($this->createMessage());
    }

    /**
     * @return Throwable[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    private function createMessage(): string
    {
        $message = '';

        foreach ($this->exceptions as $exception) {
            $message .= sprintf('%s: %s%s', get_class($exception), $exception->getMessage(), PHP_EOL);
        }

        return $message;
    }
}
