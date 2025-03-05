<?php

namespace Unleash\Client\Strategy;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;

final class ApplicationHostnameStrategyHandler extends AbstractStrategyHandler
{
    public function getStrategyName(): string
    {
        return 'applicationHostname';
    }

    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$hostnames = $this->findParameter('hostNames', $strategy)) {
            return false;
        }
        $hostnames = array_map('trim', explode(',', $hostnames));
        $enabled = in_array($context->getHostname(), $hostnames, true);
        if (!$enabled) {
            return false;
        }
        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }
        return true;
    }
}
