<?php

/**
 * Let's assume you want features to be prefixed per environment, here's a sample implementation
 */

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Variant;
use Rikudou\Unleash\Unleash;
use Rikudou\Unleash\UnleashBuilder;

require_once __DIR__ . '/_common.php';

class PrefixedUnleash implements Unleash
{
    public function __construct(private string $prefix, private Unleash $original)
    {
    }

    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool
    {
        return $this->original->isEnabled("{$this->prefix}.{$featureName}", $context, $default);
    }

    public function getVariant(string $featureName, ?UnleashContext $context = null, ?Variant $fallbackVariant = null): Variant
    {
        return $this->original->getVariant("{$this->prefix}.{$featureName}", $context, $fallbackVariant);
    }

    public function register(): bool
    {
        return $this->original->register();
    }
}

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHeader('Authorization', $apiKey)
    ->build();

$unleash = new PrefixedUnleash('myPrefix', $unleash);

var_dump($unleash->isEnabled('test')); // will ask for myPrefix.test
