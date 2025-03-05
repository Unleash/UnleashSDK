<?php

namespace Unleash\Client\Event;

use Exception;

final class FetchingDataFailedEvent extends AbstractEvent
{
    /**
     * @readonly
     * @var \Exception
     */
    private $exception;
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }
    public function getException(): Exception
    {
        return $this->exception;
    }
}
