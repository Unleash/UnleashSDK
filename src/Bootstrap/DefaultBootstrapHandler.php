<?php

namespace Unleash\Client\Bootstrap;

use JsonException;
use Override;
use Traversable;

final class DefaultBootstrapHandler implements BootstrapHandler
{
    /**
     * @throws JsonException
     */
    public function getBootstrapContents(BootstrapProvider $provider): ?string
    {
        $bootstrap = $provider->getBootstrap();
        if ($bootstrap === null) {
            return null;
        }
        if ($bootstrap instanceof Traversable) {
            $bootstrap = iterator_to_array($bootstrap);
        }
        $result = json_encode($bootstrap, 0);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        }
        assert($result !== false);
        return $result;
    }
}
