<?php

namespace Unleash\Client\Client;

use DateTimeImmutable;
use Exception;
use JsonException;
use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Enum\CacheKey;
use Unleash\Client\Helper\StringStream;
use Unleash\Client\Helper\Url;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Unleash;

final class DefaultRegistrationService implements RegistrationService
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
    /**
     * @var string|null
     */
    private $sdkName = '';
    /**
     * @var string|null
     */
    private $sdkVersion = '';
    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashConfiguration $configuration, ?string $sdkName = '', ?string $sdkVersion = '')
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
        /**
         * @deprecated use configuration sdkVersion property
         */
        $this->sdkName = $sdkName;
        /**
         * @deprecated use configuration sdkVersion property
         */
        $this->sdkVersion = $sdkVersion;
    }
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function register(iterable $strategyHandlers): bool
    {
        if (!$this->configuration->isFetchingEnabled()) {
            return false;
        }
        if ($this->hasValidCacheRegistration()) {
            return true;
        }
        if (!is_array($strategyHandlers)) {
            $strategyHandlers = iterator_to_array($strategyHandlers);
        }
        $legacySdkVersion = $this->sdkName . ':' . $this->sdkVersion;
        $request = $this->requestFactory
            ->createRequest('POST', (string) Url::appendPath($this->configuration->getUrl(), 'client/register'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'sdkVersion' => ($legacySdkVersion !== ':') ? $legacySdkVersion : $this->configuration->getSdkVersion(),
                'strategies' => array_map(function (StrategyHandler $strategyHandler) : string {
                    return $strategyHandler->getStrategyName();
                }, $strategyHandlers),
                'started' => (new DateTimeImmutable())->format('c'),
                'interval' => $this->configuration->getMetricsInterval(),
                'platformName' => PHP_SAPI,
                'platformVersion' => PHP_VERSION,
                'yggdrasilVersion' => null,
                'specVersion' => Unleash::SPECIFICATION_VERSION,
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        try {
            $response = $this->httpClient->sendRequest($request);
            $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (Exception $exception) {
            $result = false;
        }
        $this->storeCache($result);
        return $result;
    }

    private function hasValidCacheRegistration(): bool
    {
        $cache = $this->configuration->getCache();
        $staleCache = $this->configuration->getStaleCache();

        $hasNormalCache = $cache->has(CacheKey::REGISTRATION);
        $hasStaleCache = $staleCache->has(CacheKey::REGISTRATION);

        switch (true) {
            case $hasNormalCache:
                return (bool) $cache->get(CacheKey::REGISTRATION);
            case $hasStaleCache:
                return (bool) $staleCache->get(CacheKey::REGISTRATION);
            default:
                return false;
        }
    }

    private function storeCache(bool $result): void
    {
        $this->configuration->getCache()->set(CacheKey::REGISTRATION, $result, $this->configuration->getTtl());
        $this->configuration->getStaleCache()->set(CacheKey::REGISTRATION, $result, $this->configuration->getStaleTtl());
    }
}
