<?php

namespace Unleash\Client\Enum;

/**
 * @internal
 */
final class CacheKey
{
    /**
     * @var string
     */
    public const METRICS_BUCKET = 'unleash.client.metrics.bucket';

    /**
     * @var string
     */
    public const FEATURES = 'unleash.client.feature.list';

    /**
     * @var string
     */
    public const REGISTRATION = 'unleash.client.metrics.registration';

    /**
     * @var string
     */
    public const FEATURES_RESPONSE = 'unleash.client.feature.response';
}
