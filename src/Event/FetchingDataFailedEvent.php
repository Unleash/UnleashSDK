<?php

namespace Unleash\Client\Event;

use Exception;

final class FetchingDataFailedEvent extends AbstractEvent
{
    public function __construct(
        private Exception $exception,
    ) {
    }

    public function getException(): Exception
    {
        return $this->exception;
    }
}
