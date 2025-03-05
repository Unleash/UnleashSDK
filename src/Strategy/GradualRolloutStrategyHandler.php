<?php

namespace Unleash\Client\Strategy;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Stickiness\StickinessCalculator;

final class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @readonly
     */
    private StickinessCalculator $stickinessCalculator;
    public function __construct(StickinessCalculator $stickinessCalculator)
    {
        $this->stickinessCalculator = $stickinessCalculator;
    }

    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            return false;
        }
        $groupId = $this->findParameter('groupId', $strategy) ?? '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return false;
        }
        switch (strtolower($stickiness)) {
            case Stickiness::DEFAULT:
                $id = $context->getCurrentUserId() ?? $context->getSessionId() ?? random_int(1, 100_000);
                break;
            case Stickiness::RANDOM:
                $id = random_int(1, 100_000);
                break;
            case Stickiness::USER_ID:
                $id = $context->getCurrentUserId();
                break;
            case Stickiness::SESSION_ID:
                $id = $context->getSessionId();
                break;
            default:
                $id = $context->findContextValue($stickiness);
                break;
        }
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

    public function getStrategyName(): string
    {
        return 'flexibleRollout';
    }
}
