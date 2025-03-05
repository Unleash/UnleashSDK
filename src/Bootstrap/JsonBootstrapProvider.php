<?php

namespace Unleash\Client\Bootstrap;

use JsonException;
use Override;
use Unleash\Client\Exception\InvalidValueException;

final class JsonBootstrapProvider implements BootstrapProvider
{
    public function __construct(
        private readonly string $json,
    ) {
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    #[Override]
    public function getBootstrap(): array
    {
        $result = @json_decode($this->json, true);
        if (json_last_error()) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }
        if (!is_array($result)) {
            throw new InvalidValueException(sprintf(
                'The provided json string must be a valid json object, %s given.',
                gettype($result),
            ));
        }

        return $result;
    }
}
