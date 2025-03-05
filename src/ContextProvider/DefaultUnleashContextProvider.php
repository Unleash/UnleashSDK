<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashContext;

final class DefaultUnleashContextProvider implements UnleashContextProvider
{
    #[Pure]
    #[Override]
    public function getContext(): Context
    {
        return new UnleashContext();
    }
}
