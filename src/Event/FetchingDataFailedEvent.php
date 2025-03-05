<?php

namespace Unleash\Client\Event;

use Exception;

final class FetchingDataFailedEvent extends AbstractEvent
{
    /**
     * @readonly
     */
    private Exception $exception;
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }
    public function getException(): Exception
    {
        return $this->exception;
    }
}
