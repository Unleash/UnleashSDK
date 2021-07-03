<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;

interface Unleash
{
    public const SDK_VERSION = '0.9.11';

    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool;

    public function register(): bool;
}
