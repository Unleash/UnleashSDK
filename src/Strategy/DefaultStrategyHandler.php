<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;

final class DefaultStrategyHandler extends AbstractStrategyHandler
{
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }

        return true;
    }

    public function getStrategyName(): string
    {
        return 'default';
    }
}
