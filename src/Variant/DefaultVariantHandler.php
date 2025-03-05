<?php

namespace Unleash\Client\Variant;

use JetBrains\PhpStorm\Pure;
use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Stickiness\StickinessCalculator;

final class DefaultVariantHandler implements VariantHandler
{
    /**
     * @var int
     */
    private const VARIANT_HASH_SEED = 86028157;

    public function __construct(
        private readonly StickinessCalculator $stickinessCalculator,
    ) {
    }

    #[Pure]
    #[Override]
    public function getDefaultVariant(): Variant
    {
        return new DefaultVariant(
            'disabled',
            false
        );
    }

    /**
     * @param array<Variant> $variants
     */
    #[Override]
    public function selectVariant(array $variants, string $groupId, Context $context): ?Variant
    {
        $totalWeight = 0;
        foreach ($variants as $variant) {
            $totalWeight += $variant->getWeight();
        }
        if ($totalWeight <= 0) {
            return null;
        }

        if ($overridden = $this->findOverriddenVariant($variants, $context)) {
            return $overridden;
        }

        $stickiness = $this->calculateStickiness(
            $variants,
            $groupId,
            $context,
            $totalWeight,
        );

        $counter = 0;
        foreach ($variants as $variant) {
            if ($variant->getWeight() <= 0) {
                continue;
            }
            $counter += $variant->getWeight();
            if ($counter >= $stickiness) {
                return $variant;
            }
        }

        // while this is in theory possible to happen, it really cannot happen unless the Unleash server is misconfigured
        // and in that case there are bigger problems than missing code coverage

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param array<Variant> $variants
     */
    private function findOverriddenVariant(array $variants, Context $context): ?Variant
    {
        foreach ($variants as $variant) {
            foreach ($variant->getOverrides() as $override) {
                if ($context->hasMatchingFieldValue($override->getField(), $override->getValues())) {
                    return $variant;
                }
            }
        }

        return null;
    }

    /**
     * @param array<Variant> $variants
     */
    private function calculateStickiness(
        array $variants,
        string $groupId,
        Context $context,
        int $totalWeight
    ): int {
        $stickiness = $variants[0]->getStickiness();
        if ($stickiness !== Stickiness::DEFAULT) {
            $seed = $context->findContextValue($stickiness) ?? $this->randomString();
        } else {
            $seed = $context->getCurrentUserId()
                ?? $context->getSessionId()
                ?? $context->getIpAddress()
                ?? $this->randomString();
        }

        return $this->stickinessCalculator->calculate($seed, $groupId, $totalWeight, $seed = DefaultVariantHandler::VARIANT_HASH_SEED);
    }

    private function randomString(): string
    {
        return (string) random_int(1, 100_000);
    }
}
