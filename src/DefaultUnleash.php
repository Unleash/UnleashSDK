<?php

namespace Unleash\Client;

use Override;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultFeatureEnabledResult;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;
use Unleash\Client\DTO\FeatureEnabledResult;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ImpressionDataEventType;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\ImpressionDataEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Helper\Uuid;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Variant\VariantHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @var iterable<StrategyHandler>
     * @readonly
     */
    private $strategyHandlers;
    /**
     * @readonly
     * @var \Unleash\Client\Repository\UnleashRepository
     */
    private $repository;
    /**
     * @readonly
     * @var \Unleash\Client\Client\RegistrationService
     */
    private $registrationService;
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\UnleashConfiguration
     */
    private $configuration;
    /**
     * @readonly
     * @var \Unleash\Client\Metrics\MetricsHandler
     */
    private $metricsHandler;
    /**
     * @readonly
     * @var \Unleash\Client\Variant\VariantHandler
     */
    private $variantHandler;
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function __construct(iterable $strategyHandlers, UnleashRepository $repository, RegistrationService $registrationService, UnleashConfiguration $configuration, MetricsHandler $metricsHandler, VariantHandler $variantHandler)
    {
        $this->strategyHandlers = $strategyHandlers;
        $this->repository = $repository;
        $this->registrationService = $registrationService;
        $this->configuration = $configuration;
        $this->metricsHandler = $metricsHandler;
        $this->variantHandler = $variantHandler;
        if ($configuration->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }
    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $context = $context ?? $this->configuration->getContextProvider()->getContext();
        $feature = $this->findFeature($featureName, $context);
        if ($feature !== null) {
            if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
                $event = new ImpressionDataEvent(
                    ImpressionDataEventType::IS_ENABLED,
                    Uuid::v4(),
                    clone $this->configuration,
                    clone $context,
                    clone $feature,
                    null,
                );
                $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::IMPRESSION_DATA);
            }
        }
        return $this->isFeatureEnabled($feature, $context, $default)->isEnabled();
    }

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant = $fallbackVariant ?? $this->variantHandler->getDefaultVariant();
        $context = $context ?? $this->configuration->getContextProvider()->getContext();
        $feature = $this->findFeature($featureName, $context);
        $enabledResult = $this->isFeatureEnabled($feature, $context);
        $strategyVariants = (($nullsafeVariable1 = $enabledResult->getStrategy()) ? $nullsafeVariable1->getVariants() : null) ?? [];
        if (
            $feature === null
            || $enabledResult->isEnabled() === false
            || (!count($feature->getVariants()) && !count($strategyVariants))
        ) {
            return $fallbackVariant;
        }
        if (!count($strategyVariants)) {
            $variant = $this->variantHandler->selectVariant($feature->getVariants(), $featureName, $context);
        } else {
            $variant = $this->variantHandler->selectVariant($strategyVariants, (($nullsafeVariable2 = $enabledResult->getStrategy()) ? $nullsafeVariable2->getParameters() : null)['groupId'] ?? '', $context);
        }
        if ($variant !== null) {
            $this->metricsHandler->handleMetrics($feature, true, $variant);

            if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
                $event = new ImpressionDataEvent(
                    ImpressionDataEventType::GET_VARIANT,
                    Uuid::v4(),
                    clone $this->configuration,
                    clone $context,
                    clone $feature,
                    clone $variant,
                );
                $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::IMPRESSION_DATA);
            }
        }
        $resolvedVariant = $variant ?? $fallbackVariant;
        return $resolvedVariant;
    }

    public function register(): bool
    {
        return $this->registrationService->register($this->strategyHandlers);
    }

    /**
     * Finds a feature and posts events if the feature is not found.
     *
     * @param string  $featureName name of the feature to find
     * @param Context $context     the context to use
     *
     * @return Feature|null
     */
    private function findFeature(string $featureName, Context $context): ?Feature
    {
        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            $event = new FeatureToggleNotFoundEvent($context, $featureName);
            $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_NOT_FOUND,
            );
        }

        return $feature;
    }

    /**
     * Underlying method to check if a feature is enabled.
     *
     * @param Feature|null $feature the feature to check
     * @param Context      $context the context to use
     * @param bool         $default the default value to return if the feature is not found
     */
    private function isFeatureEnabled(?Feature $feature, Context $context, bool $default = false): FeatureEnabledResult
    {
        if ($feature === null) {
            return new DefaultFeatureEnabledResult($default);
        }

        if (!$feature->isEnabled()) {
            $event = new FeatureToggleDisabledEvent($feature, $context);
            $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_DISABLED,
            );

            $this->metricsHandler->handleMetrics($feature, false);

            return new DefaultFeatureEnabledResult();
        }

        $dependencies = method_exists($feature, 'getDependencies')
            ? $feature->getDependencies()
            : [];

        foreach ($dependencies as $dependency) {
            if ($this->isParentDependencySatisfied($dependency, $context, $default) !== $dependency->getExpectedState()) {
                $event = new FeatureToggleDisabledEvent($feature, $context);
                $this->configuration->getEventDispatcher()->dispatch(
                    $event,
                    UnleashEvents::FEATURE_TOGGLE_DISABLED,
                );

                $this->metricsHandler->handleMetrics($feature, false);

                return new DefaultFeatureEnabledResult();
            }
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            $strategies = iterator_to_array($strategies);
        }
        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return new DefaultFeatureEnabledResult(true);
        }

        $handlersFound = false;
        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            $handlersFound = true;
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    $this->metricsHandler->handleMetrics($feature, true);

                    return new DefaultFeatureEnabledResult(true, $strategy);
                }
            }
        }

        if (!$handlersFound) {
            $event = new FeatureToggleMissingStrategyHandlerEvent($context, $feature);
            $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER,
            );
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return new DefaultFeatureEnabledResult();
    }

    /**
     * @return array<StrategyHandler>
     */
    private function findStrategyHandlers(Strategy $strategy): array
    {
        $handlers = [];
        foreach ($this->strategyHandlers as $strategyHandler) {
            if ($strategyHandler->supports($strategy)) {
                $handlers[] = $strategyHandler;
            }
        }

        return $handlers;
    }

    private function isParentDependencySatisfied(FeatureDependency $dependency, Context $context, bool $default): bool
    {
        if (!$dependency->isResolved()) {
            return false;
        }

        $enabled = $this->isFeatureEnabled($dependency->getFeature(), $context, $default);
        if (!$enabled->isEnabled()) {
            return false;
        }

        assert($dependency->getFeature() !== null);

        if (
            method_exists($dependency->getFeature(), 'getDependencies')
            && count($dependency->getFeature()->getDependencies())
        ) {
            return false;
        }

        if ($dependency->getRequiredVariants() === null || !count($dependency->getRequiredVariants())) {
            return true;
        }

        $variant = $this->getVariant($dependency->getFeature()->getName(), $context);

        $requiredVariants = array_map(function (Variant $variant) {
            return $variant->getName();
        }, $dependency->getRequiredVariants());

        return in_array($variant->getName(), $requiredVariants, true);
    }
}
