<?php

namespace Unleash\Client\Repository;

use Exception;
use JsonException;
use LogicException;
use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Constraint;
use Unleash\Client\DTO\DefaultConstraint;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultFeatureDependency;
use Unleash\Client\DTO\DefaultSegment;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\DefaultVariantOverride;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;
use Unleash\Client\DTO\Internal\UnresolvedFeature;
use Unleash\Client\DTO\Internal\UnresolvedFeatureDependency;
use Unleash\Client\DTO\Internal\UnresolvedVariant;
use Unleash\Client\DTO\Segment;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\CacheKey;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Event\FetchingDataFailedEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Exception\HttpResponseException;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Helper\Url;
use Unleash\Client\Unleash;

/**
 * @phpstan-type ConstraintArray array{
 *     contextName: string,
 *     operator: string,
 *     values?: array<string>,
 *     value?: string,
 *     inverted?: bool,
 *     caseInsensitive?: bool
 * }
 * @phpstan-type VariantArray array{
 *      contextName: string,
 *      name: string,
 *      weight: int,
 *      stickiness?: string,
 *      payload?: VariantPayload,
 *      overrides?: array<VariantOverride>,
 *  }
 * @phpstan-type VariantPayload array{
 *        type: string,
 *        value: string,
 *    }
 * @phpstan-type VariantOverride array{
 *       contextName: string,
 *       values: array<string>,
 *       type:string,
 *       value: string,
 *   }
 * @phpstan-type StrategyArray array{
 *       constraints?: array<ConstraintArray>,
 *       variants?: array<VariantArray>,
 *       segments?: array<string>,
 *       name: string,
 *       parameters: array<string, string>,
 *   }
 * @phpstan-type SegmentArray array{
 *       id: int,
 *       constraints: array<ConstraintArray>,
 *   }
 * @phpstan-type FeatureArray array{
 *       strategies: array<StrategyArray>,
 *       variants: array<VariantArray>,
 *       name: string,
 *       enabled: bool,
 *       impressionData?: bool,
 *   }
 */
final class DefaultUnleashRepository implements UnleashRepository
{
    /**
     * @readonly
     * @var \Psr\Http\Client\ClientInterface
     */
    private $httpClient;
    /**
     * @readonly
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $requestFactory;
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\UnleashConfiguration
     */
    private $configuration;
    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashConfiguration $configuration)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
    }
    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function findFeature(string $featureName): ?Feature
    {
        $features = $this->getFeatures();
        assert(is_array($features));
        return $features[$featureName] ?? null;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws JsonException
     *
     * @return iterable<Feature>
     */
    public function getFeatures(): iterable
    {
        $features = $this->getCachedFeatures();
        if ($features === null) {
            $features = $this->fetchFeatures();
        }
        return $features;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function refreshCache(): void
    {
        $this->fetchFeatures();
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<Feature>|null
     */
    private function getCachedFeatures(): ?array
    {
        $cache = $this->configuration->getCache();

        $result = $cache->get(CacheKey::FEATURES);
        if ($result === null) {
            return null;
        }
        assert(is_array($result));

        return $result;
    }

    /**
     * @param array<Feature> $features
     *
     * @throws InvalidArgumentException
     */
    private function setCache(array $features): void
    {
        $cache = $this->configuration->getCache();
        $ttl = $this->configuration->getTtl();
        $cache->set(CacheKey::FEATURES, $features, $ttl);
    }

    /**
     * @param array{segments?: array<SegmentArray>, features?: array<FeatureArray>} $body
     *
     * @return array<Feature>
     */
    private function parseFeatures(array $body): array
    {
        /** @var array<string, DefaultFeature> $features */
        $features = [];
        $globalSegments = $this->parseSegments($body['segments'] ?? []);

        if (!isset($body['features']) || !is_array($body['features'])) {
            throw new InvalidValueException("The body isn't valid because it doesn't contain a 'features' key");
        }

        /** @var array<DefaultFeature> $unresolved */
        $unresolved = [];

        foreach ($body['features'] as $feature) {
            $strategies = [];
            foreach ($feature['strategies'] as $strategy) {
                $constraints = $this->parseConstraints($strategy['constraints'] ?? []);
                $strategyVariants = $this->parseVariants($strategy['variants'] ?? []);

                $hasNonexistentSegments = false;
                $segments = [];
                foreach ($strategy['segments'] ?? [] as $segment) {
                    if (isset($globalSegments[$segment])) {
                        $segments[] = $globalSegments[$segment];
                    } else {
                        $hasNonexistentSegments = true;
                        break;
                    }
                }
                $strategies[] = new DefaultStrategy(
                    $strategy['name'],
                    $strategy['parameters'] ?? [],
                    $constraints,
                    $segments,
                    $hasNonexistentSegments,
                    $strategyVariants,
                );
            }

            $featureVariants = $this->parseVariants($feature['variants'] ?? []);
            $dependencies = $this->parseDependencies($feature['dependencies'] ?? [], $features, $hasUnresolvedDependencies);

            $featureDto = new DefaultFeature(
                $feature['name'],
                $feature['enabled'],
                $strategies,
                $featureVariants,
                $feature['impressionData'] ?? false,
                $dependencies,
            );
            if ($hasUnresolvedDependencies) {
                $unresolved[] = $featureDto;
            } else {
                $features[$feature['name']] = $featureDto;
            }
        }

        // $features are passed by reference so that the method can modify them
        $this->handleUnresolvedDependencies($unresolved, $features);

        return $features;
    }

    /**
     * All unresolved dependencies are solved in this method (if possible).
     * The implementation is simplified, because only one level of nesting is allowed, if there ever is need for more,
     * the code will have to be much more complex.
     *
     * @param array<Feature>                $unresolved
     * @param array<string, DefaultFeature> $features
     */
    private function handleUnresolvedDependencies(array $unresolved, array &$features): void
    {
        foreach ($unresolved as $unresolvedFeature) {
            $dependencies = [];
            foreach ($unresolvedFeature->getDependencies() as $dependency) {
                // This can happen if we get multiple dependencies and at least one is unresolved.
                // We don't need to do anything with them.
                if (!$dependency instanceof UnresolvedFeatureDependency) {
                    $dependencies[] = $dependency;
                    continue;
                }

                $feature = $dependency->getFeature();
                if ($feature instanceof UnresolvedFeature) {
                    $feature = $features[$feature->getName()] ?? null;
                    // This can happen if the feature truly does not exist.
                    // Because dependencies can only be 1 level deep, we can safely assume that it cannot be resolved.
                    // If there ever is support for more levels, this will need to be rewritten.
                    if ($feature === null) {
                        $dependencies[] = $dependency;
                        continue;
                    }
                }

                $requiredVariants = [];
                foreach ($dependency->getRequiredVariants() ?? [] as $requiredVariant) {
                    // Same as with resolved dependencies, just add them directly.
                    if (!$requiredVariant instanceof UnresolvedVariant) {
                        $requiredVariants[] = $requiredVariant;
                        continue;
                    }

                    // Either find a resolved variant, or return the unresolved one.
                    $requiredVariants[] = $this->findVariant(
                        $requiredVariant->getName(),
                        $feature,
                        $requiredVariant,
                    );
                }

                // Add it as a resolved dependency, this pass is complete and nothing more can be done with the dependency.
                $dependencies[] = new DefaultFeatureDependency(
                    $feature,
                    $dependency->getExpectedState(),
                    $requiredVariants,
                );
            }

            // Everything except the dependencies is copied directly from the unresolved feature
            $features[$unresolvedFeature->getName()] = new DefaultFeature(
                $unresolvedFeature->getName(),
                $unresolvedFeature->isEnabled(),
                $unresolvedFeature->getStrategies(),
                $unresolvedFeature->getVariants(),
                $unresolvedFeature->hasImpressionData(),
                $dependencies,
            );
        }
    }

    private function findVariant(string $name, Feature $feature, Variant $defaultVariant): Variant
    {
        foreach ($feature->getVariants() as $variant) {
            if ($variant->getName() === $name) {
                return $variant;
            }
        }

        return $defaultVariant;
    }

    /**
     * @param array<array{feature: string, enabled?: bool, variants?: array<string>}> $dependencies
     * @param array<string, Feature>                                                  $features
     *
     * @return array<FeatureDependency>
     */
    private function parseDependencies(array $dependencies, array $features, ?bool &$hasUnresolvedDependencies = null): array
    {
        $hasUnresolvedDependencies = false;
        $result = [];

        foreach ($dependencies as $dependency) {
            $dependentFeatureName = $dependency['feature'];
            $expectedState = $dependency['enabled'] ?? true;
            $requiredVariants = $dependency['variants'] ?? null;

            $dependencyResolved = $this->isFeatureResolved($features, $dependentFeatureName);
            if (is_array($requiredVariants)) {
                $requiredVariants = $this->getResolvedVariants($requiredVariants, $features, $dependentFeatureName, $dependencyResolved);
            }

            $result[] = $dependencyResolved
                ? new DefaultFeatureDependency(
                    $features[$dependentFeatureName],
                    $expectedState,
                    $requiredVariants,
                )
                : new UnresolvedFeatureDependency(
                    $features[$dependentFeatureName] ?? new UnresolvedFeature($dependentFeatureName),
                    $expectedState,
                    $requiredVariants,
                );

            if (!$dependencyResolved) {
                $hasUnresolvedDependencies = true;
            }
        }

        return $result;
    }

    /**
     * @param array<string, Feature> $features
     */
    private function isFeatureResolved(array $features, string $dependentFeatureName): bool
    {
        return isset($features[$dependentFeatureName]);
    }

    /**
     * @param array<string>  $requiredVariants
     * @param array<Feature> $features
     *
     * @return array<Variant>
     */
    private function getResolvedVariants(array $requiredVariants, array $features, string $dependentFeatureName, bool &$dependencyResolved): array
    {
        return array_map(
            function (string $variantName) use ($dependentFeatureName, &$features, &$dependencyResolved) {
                if (!$dependencyResolved) {
                    return new UnresolvedVariant($variantName);
                }

                $feature = $features[$dependentFeatureName];

                $variants = $feature->getVariants();
                foreach ($variants as $variant) {
                    if ($variant->getName() === $variantName) {
                        return $variant;
                    }
                }

                foreach ($feature->getStrategies() as $strategy) {
                    foreach ($strategy->getVariants() as $variant) {
                        if ($variant->getName() === $variantName) {
                            return $variant;
                        }
                    }
                }

                $dependencyResolved = false;

                return new UnresolvedVariant($variantName);
            },
            $requiredVariants,
        );
    }

    private function getBootstrappedResponse(): ?string
    {
        return $this->configuration->getBootstrapHandler()->getBootstrapContents(
            $this->configuration->getBootstrapProvider(),
        );
    }

    private function getLastValidState(): ?string
    {
        if (!$this->configuration->getStaleCache()->has(CacheKey::FEATURES_RESPONSE)) {
            return null;
        }

        $value = $this->configuration->getStaleCache()->get(CacheKey::FEATURES_RESPONSE);
        assert(is_string($value));

        return $value;
    }

    private function setLastValidState(string $data): void
    {
        $this->configuration->getStaleCache()->set(
            CacheKey::FEATURES_RESPONSE,
            $data,
            $this->configuration->getStaleTtl(),
        );
    }

    /**
     * @param array<SegmentArray> $segmentsRaw
     *
     * @return array<Segment>
     */
    private function parseSegments(array $segmentsRaw): array
    {
        $result = [];
        foreach ($segmentsRaw as $segmentRaw) {
            $result[$segmentRaw['id']] = new DefaultSegment(
                $segmentRaw['id'],
                $this->parseConstraints($segmentRaw['constraints']),
            );
        }

        return $result;
    }

    /**
     * @param array<ConstraintArray> $constraintsRaw
     *
     * @return array<Constraint>
     */
    private function parseConstraints(array $constraintsRaw): array
    {
        $constraints = [];

        foreach ($constraintsRaw as $constraint) {
            $constraints[] = new DefaultConstraint(
                $constraint['contextName'],
                $constraint['operator'],
                $constraint['values'] ?? null,
                $constraint['value'] ?? null,
                $constraint['inverted'] ?? false,
                $constraint['caseInsensitive'] ?? false,
            );
        }

        return $constraints;
    }

    /**
     * @param array<VariantArray> $variantsRaw
     *
     * @return array<Variant>
     */
    private function parseVariants(array $variantsRaw): array
    {
        $variants = [];

        foreach ($variantsRaw as $variant) {
            $overrides = [];
            foreach ($variant['overrides'] ?? [] as $override) {
                $overrides[] = new DefaultVariantOverride($override['contextName'], $override['values']);
            }
            $variants[] = new DefaultVariant(
                $variant['name'],
                true,
                $variant['weight'],
                $variant['stickiness'] ?? Stickiness::DEFAULT,
                isset($variant['payload'])
                    ? new DefaultVariantPayload($variant['payload']['type'], $variant['payload']['value'])
                    : null,
                $overrides,
            );
        }

        return $variants;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     *
     * @return array<Feature>
     */
    private function fetchFeatures(): array
    {
        $data = null;
        if (!$this->configuration->isFetchingEnabled()) {
            if (!$rawData = $this->getBootstrappedResponse()) {
                throw new LogicException('Fetching of Unleash api is disabled but no bootstrap is provided');
            }
        } else {
            $request = $this->requestFactory
                ->createRequest('GET', (string) Url::appendPath($this->configuration->getUrl(), 'client/features'))
                // TODO: remove non-standard headers
                ->withHeader('UNLEASH-APPNAME', $this->configuration->getAppName())
                ->withHeader('UNLEASH-INSTANCEID', $this->configuration->getInstanceId())
                ->withHeader('Unleash-Interval', (string) ($this->configuration->getTtl() * 1000))
                ->withHeader('Unleash-Client-Spec', Unleash::SPECIFICATION_VERSION);

            foreach ($this->configuration->getHeaders() as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            try {
                $response = $this->httpClient->sendRequest($request);
                if ($response->getStatusCode() === 200) {
                    $rawData = (string) $response->getBody();
                    $data = json_decode($rawData, true);
                    if (($lastError = json_last_error()) !== JSON_ERROR_NONE) {
                        throw new InvalidValueException(
                            sprintf("JsonException: '%s'", json_last_error_msg()),
                            $lastError
                        );
                    }
                    $this->setLastValidState($rawData);
                } else {
                    throw new HttpResponseException("Invalid status code: '{$response->getStatusCode()}'");
                }
            } catch (Exception $exception) {
                $this->configuration->getEventDispatcher()->dispatch(
                    new FetchingDataFailedEvent($exception),
                    UnleashEvents::FETCHING_DATA_FAILED,
                );
                $rawData = $this->getLastValidState();
            }
            $rawData = $rawData ?? $this->getBootstrappedResponse();
            if ($rawData === null) {
                throw new HttpResponseException(sprintf(
                    'Got invalid response code when getting features and no default bootstrap provided: %s',
                    isset($response) ? $response->getStatusCode() : 'unknown response status code'
                ), 0, $exception ?? null);
            }
        }

        if ($data === null) {
            $data = json_decode($rawData, true);
        }

        assert(is_array($data));
        $features = $this->parseFeatures($data);
        $this->setCache($features);

        return $features;
    }
}
