<?php

namespace Unleash\Client;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Traversable;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\Bootstrap\FileBootstrapProvider;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Exception\CyclicDependencyException;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Helper\Builder\CacheAware;
use Unleash\Client\Helper\Builder\ConfigurationAware;
use Unleash\Client\Helper\Builder\HttpClientAware;
use Unleash\Client\Helper\Builder\MetricsSenderAware;
use Unleash\Client\Helper\Builder\RequestFactoryAware;
use Unleash\Client\Helper\Builder\StaleCacheAware;
use Unleash\Client\Helper\Builder\StickinessCalculatorAware;
use Unleash\Client\Helper\DefaultImplementationLocator;
use Unleash\Client\Helper\UnleashBuilderContainer;
use Unleash\Client\Metrics\DefaultMetricsBucketSerializer;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Metrics\MetricsBucketSerializer;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Metrics\MetricsSender;
use Unleash\Client\Repository\DefaultUnleashProxyRepository;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\ApplicationHostnameStrategyHandler;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Variant\DefaultVariantHandler;
use Unleash\Client\Variant\VariantHandler;

final class UnleashBuilder
{
    /**
     * @var \Unleash\Client\Helper\DefaultImplementationLocator
     */
    private $defaultImplementationLocator;
    /**
     * @var string|null
     */
    private $appUrl;
    /**
     * @var string|null
     */
    private $instanceId;
    /**
     * @var string|null
     */
    private $appName;
    /**
     * @var \Psr\Http\Client\ClientInterface|null
     */
    private $httpClient;
    /**
     * @var \Psr\Http\Message\RequestFactoryInterface|null
     */
    private $requestFactory;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $staleCache;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $metricsCache;
    /**
     * @var int|null
     */
    private $cacheTtl;
    /**
     * @var int|null
     */
    private $staleTtl;
    /**
     * @var \Unleash\Client\Client\RegistrationService|null
     */
    private $registrationService;
    /**
     * @var bool
     */
    private $autoregister = true;
    /**
     * @var bool|null
     */
    private $metricsEnabled;
    /**
     * @var int|null
     */
    private $metricsInterval;
    /**
     * @var \Unleash\Client\ContextProvider\UnleashContextProvider|null
     */
    private $contextProvider;
    /**
     * @var \Unleash\Client\Bootstrap\BootstrapProvider|null
     */
    private $bootstrapProvider;
    /**
     * @var \Unleash\Client\Bootstrap\BootstrapHandler|null
     */
    private $bootstrapHandler;
    /**
     * @var string|null
     */
    private $proxyKey;
    /**
     * @var bool
     */
    private $fetchingEnabled = true;
    /**
     * @var array<string,string>
     */
    private $headers = [];
    /**
     * @var array<StrategyHandler>
     */
    private $strategies;
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
     */
    private $eventDispatcher;
    /**
     * @var array<EventSubscriberInterface>
     */
    private $eventSubscribers = [];
    /**
     * @var \Unleash\Client\Metrics\MetricsHandler|null
     */
    private $metricsHandler;
    /**
     * @var \Unleash\Client\Variant\VariantHandler|null
     */
    private $variantHandler;
    /**
     * @var \Unleash\Client\Metrics\MetricsBucketSerializer|null
     */
    private $metricsBucketSerializer;
    public function __construct()
    {
        $this->defaultImplementationLocator = new DefaultImplementationLocator();
        if (class_exists(EventDispatcher::class)) {
            $this->eventDispatcher = new EventDispatcher();
        }

        $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            $rolloutStrategyHandler,
            new ApplicationHostnameStrategyHandler(),
            new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
        ];
    }
    public static function create(): self
    {
        return new self();
    }
    public static function createForGitlab(): self
    {
        return self::create()
            ->withMetricsEnabled(false)
            ->withAutomaticRegistrationEnabled(false);
    }
    public function withAppUrl(string $appUrl): self
    {
        return $this->with('appUrl', $appUrl);
    }
    public function withInstanceId(string $instanceId): self
    {
        return $this->with('instanceId', $instanceId);
    }
    public function withAppName(string $appName): self
    {
        return $this->with('appName', $appName);
    }
    public function withGitlabEnvironment(string $environment): self
    {
        return $this->withAppName($environment);
    }
    public function withHttpClient(ClientInterface $client): self
    {
        return $this->with('httpClient', $client);
    }
    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        return $this->with('requestFactory', $requestFactory);
    }
    public function withStrategies(StrategyHandler ...$strategies): self
    {
        return $this->with('strategies', $strategies);
    }
    public function withStrategy(StrategyHandler $strategy): self
    {
        return $this->withStrategies(...array_merge($this->strategies, [$strategy]));
    }
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null): self
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }
        return $result;
    }
    public function withStaleCacheHandler(?CacheInterface $cache): self
    {
        return $this->with('staleCache', $cache);
    }
    public function withMetricsCacheHandler(?CacheInterface $cache): self
    {
        return $this->with('metricsCache', $cache);
    }
    public function withCacheTimeToLive(int $timeToLive): self
    {
        return $this->with('cacheTtl', $timeToLive);
    }
    public function withHeader(string $header, string $value): self
    {
        return $this->with('headers', array_merge($this->headers, [$header => $value]));
    }
    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        return $this->with('headers', $headers);
    }
    public function withRegistrationService(RegistrationService $registrationService): self
    {
        return $this->with('registrationService', $registrationService);
    }
    public function withAutomaticRegistrationEnabled(bool $enabled): self
    {
        return $this->with('autoregister', $enabled);
    }
    public function withMetricsEnabled(bool $enabled): self
    {
        return $this->with('metricsEnabled', $enabled);
    }
    public function withMetricsInterval(int $milliseconds): self
    {
        return $this->with('metricsInterval', $milliseconds);
    }
    public function withContextProvider(?UnleashContextProvider $contextProvider): self
    {
        return $this->with('contextProvider', $contextProvider);
    }
    public function withBootstrapHandler(?BootstrapHandler $handler): self
    {
        return $this->with('bootstrapHandler', $handler);
    }
    public function withBootstrapProvider(?BootstrapProvider $provider): self
    {
        return $this->with('bootstrapProvider', $provider);
    }
    /**
     * @param array<mixed>|Traversable<mixed>|JsonSerializable|null|string $bootstrap
     */
    public function withBootstrap($bootstrap): self
    {
        if ($bootstrap === null) {
            $provider = new EmptyBootstrapProvider();
        } elseif (is_string($bootstrap)) {
            $provider = new JsonBootstrapProvider($bootstrap);
        } else {
            $provider = new JsonSerializableBootstrapProvider($bootstrap);
        }
        return $this->withBootstrapProvider($provider);
    }
    /**
     * @param string|\SplFileInfo|null $file
     */
    public function withBootstrapFile($file): self
    {
        if ($file === null) {
            $provider = new EmptyBootstrapProvider();
        } else {
            $provider = new FileBootstrapProvider($file);
        }
        return $this->withBootstrapProvider($provider);
    }
    public function withBootstrapUrl(?string $url): self
    {
        return $this->withBootstrapFile($url);
    }
    public function withFetchingEnabled(bool $enabled): self
    {
        return $this->with('fetchingEnabled', $enabled);
    }
    public function withEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
    {
        return $this->with('eventDispatcher', $eventDispatcher);
    }
    public function withEventSubscriber(EventSubscriberInterface $eventSubscriber): self
    {
        $subscribers = $this->eventSubscribers;
        $subscribers[] = $eventSubscriber;

        return $this->with('eventSubscribers', $subscribers);
    }
    public function withStaleTtl(?int $ttl): self
    {
        return $this->with('staleTtl', $ttl);
    }
    public function withMetricsHandler(?MetricsHandler $metricsHandler): self
    {
        return $this->with('metricsHandler', $metricsHandler);
    }
    public function withVariantHandler(?VariantHandler $variantHandler): self
    {
        return $this->with('variantHandler', $variantHandler);
    }
    public function withProxy(?string $proxyKey): self
    {
        return $this->with('proxyKey', $proxyKey);
    }
    public function withMetricsBucketSerializer(?MetricsBucketSerializer $metricsBucketSerializer): self
    {
        return $this->with('metricsBucketSerializer', $metricsBucketSerializer);
    }
    public function buildRepository(): UnleashRepository
    {
        $dependencyContainer = $this->initializeContainerWithConfiguration();

        return $this->createRepository($dependencyContainer);
    }
    public function build(): Unleash
    {
        $dependencyContainer = $this->initializeContainerWithConfiguration();
        $repository = $this->createRepository($dependencyContainer);

        assert($dependencyContainer->getConfiguration() !== null);
        $metricsSender = new DefaultMetricsSender($dependencyContainer->getHttpClient(), $dependencyContainer->getRequestFactory(), $dependencyContainer->getConfiguration());

        $dependencyContainer = $this->createContainer($dependencyContainer->getCache(), $dependencyContainer->getStaleCache(), $dependencyContainer->getHttpClient(), $dependencyContainer->getRequestFactory(), $dependencyContainer->getContextProvider(), $dependencyContainer->getBootstrapHandler(), $dependencyContainer->getBootstrapProvider(), $dependencyContainer->getEventDispatcher(), $dependencyContainer->getMetricsBucketSerializer(), $dependencyContainer->getMetricsCache(), $metricsSender, $dependencyContainer->getConfiguration());

        assert($dependencyContainer->getConfiguration() !== null);
        $registrationService = $this->registrationService ?? new DefaultRegistrationService($dependencyContainer->getHttpClient(), $dependencyContainer->getRequestFactory(), $dependencyContainer->getConfiguration());
        $metricsHandler = $this->metricsHandler ?? new DefaultMetricsHandler($metricsSender, $dependencyContainer->getConfiguration());
        $variantHandler = $this->variantHandler ?? new DefaultVariantHandler(new MurmurHashCalculator());

        // initialization of dependencies
        foreach ($this->strategies as $strategy) {
            $this->initializeServices($strategy, $dependencyContainer);
        }
        $this->initializeServices($repository, $dependencyContainer);
        $this->initializeServices($registrationService, $dependencyContainer);
        $this->initializeServices($metricsHandler, $dependencyContainer);
        $this->initializeServices($variantHandler, $dependencyContainer);

        assert($dependencyContainer->getConfiguration() !== null);
        if ($this->proxyKey !== null) {
            $dependencyContainer->getConfiguration()->setProxyKey($this->proxyKey);
            $dependencyContainer->getConfiguration()->setHeaders(array_merge($this->headers, ['Authorization' => $this->proxyKey]));
            $proxyRepository = new DefaultUnleashProxyRepository($dependencyContainer->getConfiguration(), $dependencyContainer->getHttpClient(), $dependencyContainer->getRequestFactory());

            return new DefaultProxyUnleash($proxyRepository, $metricsHandler);
        }

        return new DefaultUnleash($this->strategies, $repository, $registrationService, $dependencyContainer->getConfiguration(), $metricsHandler, $variantHandler);
    }
    /**
     * @return array<int, string>
     */
    public function initializeBasicAttributes(): array
    {
        // basic scalar attributes
        $appUrl = $this->appUrl;
        $instanceId = $this->instanceId;
        $appName = $this->appName;

        if (!$this->fetchingEnabled) {
            $appUrl = $appUrl ?? 'http://127.0.0.1';
            $instanceId = $instanceId ?? 'dev';
            $appName = $appName ?? 'dev';
        }

        if ($appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($appName === null) {
            throw new InvalidValueException(
                "App name must be set, please use 'withAppName()' or 'withGitlabEnvironment()' method"
            );
        }

        return [$appUrl, $instanceId, $appName];
    }
    private function createContainer(CacheInterface $cache, CacheInterface $staleCache, ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashContextProvider $contextProvider, BootstrapHandler $bootstrapHandler, BootstrapProvider $bootstrapProvider, EventDispatcherInterface $eventDispatcher, MetricsBucketSerializer $metricsBucketSerializer, CacheInterface $metricsCache, ?MetricsSender $metricsSender = null, ?UnleashConfiguration $configuration = null): UnleashBuilderContainer
    {
        return new UnleashBuilderContainer($cache, $staleCache, $httpClient, $metricsSender, $metricsCache, $requestFactory, new MurmurHashCalculator(), $configuration, $contextProvider, $bootstrapHandler, $bootstrapProvider, $eventDispatcher, $metricsBucketSerializer);
    }
    private function initializeContainerWithConfiguration(): UnleashBuilderContainer
    {
        [$appUrl, $instanceId, $appName] = $this->initializeBasicAttributes();
        $cache = $this->cache ?? $this->defaultImplementationLocator->findCache();
        $httpClient = $this->httpClient ?? $this->defaultImplementationLocator->findHttpClient();
        $requestFactory = $this->requestFactory ?? $this->defaultImplementationLocator->findRequestFactory();

        if (!$cache) {
            throw new InvalidValueException(
                sprintf(
                    "No cache implementation provided, please use 'withCacheHandler()' method or install one of officially supported clients: '%s'",
                    implode("', '", $this->defaultImplementationLocator->getCachePackages())
                )
            );
        }
        if (!$httpClient) {
            throw new InvalidValueException(
                "No http client provided, please use 'withHttpClient()' method or install a package providing 'psr/http-client-implementation'."
            );
        }
        /**
         * This will only be thrown if a HTTP client was found, but a request factory is not.
         * Due to how php-http/discovery works, this scenario is unlikely to happen.
         * See linked comment for more info.
         *
         * https://github.com/Unleash/unleash-client-php/pull/27#issuecomment-920764416
         */
        // @codeCoverageIgnoreStart
        if (!$requestFactory) {
            throw new InvalidValueException(
                "No request factory provided, please use 'withRequestFactory()' method or install a package providing 'psr/http-factory-implementation'."
            );
        }
        // @codeCoverageIgnoreEnd

        assert($cache instanceof CacheInterface);
        assert($httpClient instanceof ClientInterface);
        assert($requestFactory instanceof RequestFactoryInterface);

        $staleCache = $this->staleCache ?? $cache;
        $metricsCache = $this->metricsCache ?? $cache;

        $contextProvider = $this->contextProvider ?? new DefaultUnleashContextProvider();
        $bootstrapHandler = $this->bootstrapHandler ?? new DefaultBootstrapHandler();
        $bootstrapProvider = $this->bootstrapProvider ?? new EmptyBootstrapProvider();
        $eventDispatcher = $this->eventDispatcher ?? new EventDispatcher();
        foreach ($this->eventSubscribers as $eventSubscriber) {
            $eventDispatcher->addSubscriber($eventSubscriber);
        }
        $metricsBucketSerializer = $this->metricsBucketSerializer ?? new DefaultMetricsBucketSerializer();

        $dependencyContainer = $this->createContainer(
            $cache,
            $staleCache,
            $httpClient,
            $requestFactory,
            $contextProvider,
            $bootstrapHandler,
            $bootstrapProvider,
            $eventDispatcher,
            $metricsBucketSerializer,
            $metricsCache
        );

        // initialize services
        $this->initializeServices($contextProvider, $dependencyContainer);
        $this->initializeServices($bootstrapHandler, $dependencyContainer);
        $this->initializeServices($bootstrapProvider, $dependencyContainer);
        if ($eventDispatcher !== null) {
            $this->initializeServices($eventDispatcher, $dependencyContainer);
        }
        foreach ($this->eventSubscribers as $eventSubscriber) {
            $this->initializeServices($eventSubscriber, $dependencyContainer);
        }

        $configuration = new UnleashConfiguration($appUrl, $appName, $instanceId);
        $configuration
            ->setCache($dependencyContainer->getCache())
            ->setStaleCache($dependencyContainer->getStaleCache())
            ->setMetricsCache($dependencyContainer->getMetricsCache())
            ->setTtl($this->cacheTtl ?? $configuration->getTtl())
            ->setStaleTtl($this->staleTtl ?? $configuration->getStaleTtl())
            ->setMetricsEnabled($this->metricsEnabled ?? $configuration->isMetricsEnabled())
            ->setMetricsInterval($this->metricsInterval ?? $configuration->getMetricsInterval())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setContextProvider($dependencyContainer->getContextProvider())
            ->setBootstrapHandler($dependencyContainer->getBootstrapHandler())
            ->setBootstrapProvider($dependencyContainer->getBootstrapProvider())
            ->setFetchingEnabled($this->fetchingEnabled)
            ->setEventDispatcher($dependencyContainer->getEventDispatcher())
            ->setMetricsBucketSerializer($dependencyContainer->getMetricsBucketSerializer());

        return $this->createContainer($cache, $staleCache, $httpClient, $requestFactory, $contextProvider, $bootstrapHandler, $bootstrapProvider, $eventDispatcher, $metricsBucketSerializer, $metricsCache, null, $configuration);
    }
    /**
     * @param mixed $value
     */
    private function with(string $property, $value): self
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
    private function initializeServices(object $target, UnleashBuilderContainer $container): void
    {
        if ($target instanceof CacheAware) {
            $target->setCache($container->getCache());
        }
        if ($target instanceof ConfigurationAware) {
            if ($configuration = $container->getConfiguration()) {
                $target->setConfiguration($configuration);
            } else {
                throw new CyclicDependencyException(sprintf("A dependency '%s' is tagged as ConfigurationAware but that would cause a cyclic dependency as it needs to be part of Configuration", get_class($target)));
            }
        }
        if ($target instanceof HttpClientAware) {
            $target->setHttpClient($container->getHttpClient());
        }
        if ($target instanceof MetricsSenderAware) {
            if ($sender = $container->getMetricsSender()) {
                $target->setMetricsSender($sender);
            } else {
                throw new CyclicDependencyException(sprintf("A dependency '%s' is tagged as MetricsSenderAware but MetricsSender is not available for this type of dependency", get_class($target)));
            }
        }
        if ($target instanceof RequestFactoryAware) {
            $target->setRequestFactory($container->getRequestFactory());
        }
        if ($target instanceof StaleCacheAware) {
            $target->setStaleCache($container->getStaleCache());
        }
        if ($target instanceof StickinessCalculatorAware) {
            $target->setStickinessCalculator($container->getStickinessCalculator());
        }
    }
    /**
     * @param UnleashBuilderContainer $dependencyContainer
     *
     * @return DefaultUnleashRepository
     */
    private function createRepository(UnleashBuilderContainer $dependencyContainer): DefaultUnleashRepository
    {
        assert($dependencyContainer->getConfiguration() !== null);

        return new DefaultUnleashRepository($dependencyContainer->getHttpClient(), $dependencyContainer->getRequestFactory(), $dependencyContainer->getConfiguration());
    }
}
