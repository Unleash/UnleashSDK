<?php

namespace Rikudou\Unleash\Repository;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\DefaultFeature;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\DefaultVariant;
use Rikudou\Unleash\DTO\DefaultVariantOverride;
use Rikudou\Unleash\DTO\DefaultVariantPayload;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Exception\HttpResponseException;

final class DefaultUnleashRepository implements UnleashRepository
{
    private const CACHE_KEY_FEATURES = 'rikudou.unleash.feature.list';

    /**
     * @param array<string,string> $headers
     *
     * @internal
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UnleashConfiguration $configuration,
        private array $headers = [],
    ) {
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
        if (!$features = $this->getCachedFeatures()) {
            $request = $this->requestFactory
                ->createRequest('GET', $this->configuration->getUrl() . 'client/features')
                ->withHeader('UNLEASH-APPNAME', $this->configuration->getAppName())
                ->withHeader('UNLEASH-INSTANCEID', $this->configuration->getInstanceId());

            foreach ($this->headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new HttpResponseException(
                    'Got invalid response code when getting features: ' . $response->getStatusCode()
                );
            }
            $features = $this->parseFeatures($response->getBody()->getContents());
            $this->setCache($features);
        }

        return $features;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<Feature>|null
     */
    private function getCachedFeatures(): ?array
    {
        $cache = $this->configuration->getCache();
        if ($cache === null) {
            return null;
        }

        if (!$cache->has(self::CACHE_KEY_FEATURES)) {
            return null;
        }

        return $cache->get(self::CACHE_KEY_FEATURES, []);
    }

    /**
     * @param array<Feature> $features
     *
     * @throws InvalidArgumentException
     */
    private function setCache(array $features): void
    {
        $cache = $this->configuration->getCache();
        if ($cache === null) {
            return;
        }

        $cache->set(self::CACHE_KEY_FEATURES, $features, $this->configuration->getTtl());
    }

    /**
     * @throws JsonException
     *
     * @return array<Feature>
     */
    private function parseFeatures(string $rawBody): array
    {
        $features = [];
        $body = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        foreach ($body['features'] as $feature) {
            $strategies = [];
            $variants = [];

            foreach ($feature['strategies'] as $strategy) {
                $strategies[] = new DefaultStrategy($strategy['name'], $strategy['parameters'] ?? []);
            }
            foreach ($feature['variants'] ?? [] as $variant) {
                $overrides = [];
                foreach ($variant['overrides'] ?? [] as $override) {
                    $overrides[] = new DefaultVariantOverride($override['contextName'], $override['values']);
                }
                $variants[] = new DefaultVariant(
                    $variant['name'],
                    true, // todo
                    $variant['weight'],
                    $variant['stickiness'] ?? Stickiness::DEFAULT,
                    isset($variant['payload'])
                        ? new DefaultVariantPayload($variant['payload']['type'], $variant['payload']['value'])
                        : null,
                    $overrides,
                );
            }
            $features[$feature['name']] = new DefaultFeature(
                $feature['name'],
                $feature['enabled'],
                $strategies,
                $variants,
            );
        }

        return $features;
    }
}
