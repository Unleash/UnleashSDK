<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Override;
use Traversable;

final class EmptyBootstrapProvider implements BootstrapProvider
{
    #[Override]
    public function getBootstrap(): array|JsonSerializable|Traversable|null
    {
        return null;
    }
}
