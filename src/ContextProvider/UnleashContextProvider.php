<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;

interface UnleashContextProvider
{
    public function getContext(): Context;
}
