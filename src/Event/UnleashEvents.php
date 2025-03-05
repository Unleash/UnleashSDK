<?php

namespace Unleash\Client\Event;

final class UnleashEvents
{
    /**
     * When a feature toggle is not found this event gets triggered.
     *
     * @Event("Unleash\Client\Event\FeatureToggleNotFoundEvent")
     * @var string
     */
    public const FEATURE_TOGGLE_NOT_FOUND = 'unleash.event.toggle.not_found';

    /**
     * Triggered when a feature toggle is disabled.
     *
     * @Event("Unleash\Client\Event\FeatureToggleDisabledEvent")
     * @var string
     */
    public const FEATURE_TOGGLE_DISABLED = 'unleash.event.toggle.disabled';

    /**
     * Triggered when no strategy handler has been found for the feature.
     *
     * @Event("Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent")
     * @var string
     */
    public const FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER = 'unleash.event.toggle.missing_strategy_handler';

    /**
     * Triggered when fetching features from server fails.
     *
     * @Event("Unleash\Client\Event\FetchingDataFailedEvent")
     * @var string
     */
    public const FETCHING_DATA_FAILED = 'unleash.event.server.fetching_failed';

    /**
     * Triggered when feature has impression data enabled.
     *
     * @Event("Unleash\Client\Event\ImpressionDataEvent")
     * @var string
     */
    public const IMPRESSION_DATA = 'unleash.events.impression_data';
}
