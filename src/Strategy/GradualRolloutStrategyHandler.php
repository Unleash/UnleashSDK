<?php

namespace Unleash\Client\Strategy;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Stickiness\StickinessCalculator;

final class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(
        private StickinessCalculator $stickinessCalculator
    ) {
    }

    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            return false;
        }
        $groupId = $this->findParameter('groupId', $strategy) ?? '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return false;
        }

        $id = match (strtolower($stickiness)) {
            Stickiness::DEFAULT => $context->getCurrentUserId() ?? $context->getSessionId() ?? random_int(1, 100_000),
            Stickiness::RANDOM => random_int(1, 100_000),
            Stickiness::USER_ID => $context->getCurrentUserId(),
            Stickiness::SESSION_ID => $context->getSessionId(),
            default => $context->findContextValue($stickiness),
        };
        if ($id === null) {
            return false;
        }

        $normalized = $this->stickinessCalculator->calculate((string) $id, $groupId);

        $enabled = $normalized <= (int) $rollout;

        if (!$enabled) {
            return false;
        }

        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }

        return true;
    }

    #[Override]
    public function getStrategyName(): string
    {
        return 'flexibleRollout';
    }
}
