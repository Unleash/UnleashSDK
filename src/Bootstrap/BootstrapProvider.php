<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;

interface BootstrapProvider
{
    /**
     * @return array<mixed>|JsonSerializable|Traversable<mixed>|null
     */
    public function getBootstrap();
}
