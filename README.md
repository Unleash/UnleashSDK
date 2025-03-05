# This branch is auto generated
[![Tests](https://github.com/Unleash/unleash-client-php/actions/workflows/tests.yaml/badge.svg)](https://github.com/Unleash/unleash-client-php/actions/workflows/tests.yaml)
[![Tests (8.x)](https://github.com/Unleash/unleash-client-php/actions/workflows/tests-8.x.yaml/badge.svg)](https://github.com/Unleash/unleash-client-php/actions/workflows/tests-8.x.yaml)
[![Tests (7.x)](https://github.com/Unleash/unleash-client-php/actions/workflows/tests-7.x.yaml/badge.svg)](https://github.com/Unleash/unleash-client-php/actions/workflows/tests-7.x.yaml)
[![Coverage Status](https://img.shields.io/coverallsCoverage/github/Unleash/unleash-client-php?label=Code%20Coverage)](https://coveralls.io/github/Unleash/unleash-client-php?branch=main)
[![Download](https://img.shields.io/packagist/dt/unleash/client.svg)](https://packagist.org/packages/unleash/client)

<!-- TOC -->
  * [Unleash client SDK](#unleash-client-sdk)
  * [Migrating](#migrating)
  * [Installation](#installation)
  * [Usage](#usage)
    * [Builder](#builder)
      * [Required parameters](#required-parameters)
      * [Optional parameters](#optional-parameters)
      * [Returning intermediate objects](#returning-intermediate-objects)
  * [Proxy SDK](#proxy-sdk)
  * [Caching](#caching)
  * [Bootstrapping](#bootstrapping)
    * [Custom bootstrap provider](#custom-bootstrap-provider)
    * [Disabling communication with Unleash server](#disabling-communication-with-unleash-server)
  * [Strategies](#strategies)
    * [Default strategy](#default-strategy)
    * [IP address strategy](#ip-address-strategy)
    * [User ID strategy](#user-id-strategy)
    * [Gradual rollout strategy](#gradual-rollout-strategy)
    * [Hostname strategy](#hostname-strategy)
    * [Context provider](#context-provider)
    * [Custom strategies](#custom-strategies)
  * [Variants](#variants)
  * [Client registration](#client-registration)
  * [Metrics](#metrics)
  * [Custom headers via middleware](#custom-headers-via-middleware)
  * [Constraints](#constraints)
  * [GitLab specifics](#gitlab-specifics)
<!-- TOC -->

## Unleash client SDK

A PHP implementation of the [Unleash protocol](https://www.getunleash.io/)
aka [Feature Flags](https://docs.gitlab.com/ee/operations/feature_flags.html) in GitLab.

You may also be interested in the [Symfony Bundle](https://github.com/Unleash/unleash-client-symfony) for this package.

> Unleash allows you to gradually release your app's feature before doing a full release based on multiple strategies
> like releasing to only specific users or releasing to a percentage of your user base. Read more in the above linked
> documentations.

## Migrating

If you're migrating from 1.x to 2.x, you can read the [migration guide](doc/migrating-1.x-2.x.md).

## Installation

`composer require unleash/client`

Requires PHP 7.2 or newer.

> You will also need some implementation of [PSR-18](https://packagist.org/providers/psr/http-client-implementation)
> and [PSR-17](https://packagist.org/providers/psr/http-factory-implementation), for example 
> [Guzzle](https://packagist.org/packages/guzzlehttp/guzzle) 
> and [PSR-16](https://packagist.org/providers/psr/simple-cache-implementation), for example 
> [Symfony Cache](https://packagist.org/packages/symfony/cache).
> Example:

`composer require unleash/client guzzlehttp/guzzle symfony/cache`

or

`composer require unleash/client symfony/http-client nyholm/psr7 symfony/cache`

If you want to make use of events you also need to install `symfony/event-dispatcher`. 
See [event documentation here](doc/events.md).

## Usage

The basic usage is getting the `Unleash` object and checking for a feature:

```php
<?php

use Unleash\Client\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

if ($unleash->isEnabled('some-feature-name')) {
    // do something
}
```

You can (and in some cases you must) also provide a context object. If the feature doesn't exist on the server
you will get `false` from `isEnabled()`, but you can change the default value to `true`.

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

$context = new UnleashContext(
    currentUserId: 'some-user-id-from-app',
    ipAddress: '127.0.0.1', // will be populated automatically from $_SERVER if needed
    sessionId: 'sess-123456', // will be populated automatically via session_id() if needed
);

// or using pre php 8 style:

$context = (new UnleashContext())
    ->setCurrentUserId('some-user-id-from-app')
    ->setIpAddress('127.0.0.1')
    ->setSessionId('sess-123456');

if ($unleash->isEnabled('some-feature', $context)) {
    // do something
}

// changing the default value for non-existent features
if ($unleash->isEnabled('nonexistent-feature', $context, true)) {
    // do something
}
```

### Builder

The builder contains many configuration options, and it's advised to always use the builder to construct an Unleash
instance. The builder is immutable.

The builder object can be created using the `create()` static method or by using its constructor:

```php
<?php

use Unleash\Client\UnleashBuilder;

// both are the same
$builder = new UnleashBuilder();
$builder = UnleashBuilder::create();
```

You can replace various parts of the Unleash SDK with custom implementation using the builder, like custom registration
service, custom metrics handler and so on.

Replaceable parts (some of them have further documentation below):

- registration service (`withRegistrationService()`)
- context provider (`withContextProvider()`)
- bootstrap handler (`withBootstrapHandler()`)
- event dispatcher (`withEventDispatcher()`)
- metrics handler (`withMetricsHandler()`)
- variant handler (`withVariantHandler()`)

Dependencies can be injected by implementing one of the following interfaces from the `Unleash\Client\Helper\Builder` namespace:

- `CacheAware` - injects standard cache
- `ConfigurationAware` - injects the global configuration object
- `HttpClientAware` - injects the http client
- `MetricsSenderAware` - injects the metrics sender service
- `RequestFactoryAware` - injects the request factory
- `StaleCacheAware` - injects the stale cache handler
- `StickinessCalculatorAware` - injects the stickiness calculator used for calculating stickiness in gradual rollout strategy

In addition to the parts above these interfaces can also be implemented by these kinds of classes:

- bootstrap providers
- event subscribers
- strategy handlers

> Some classes cannot depend on certain objects, namely any object that is present in the configuration cannot implement
> ConfigurationAware (to avoid circular dependency). The same classes also cannot implement MetricsSenderAware because
> metrics sender depends on the configuration object. You will get a \Unleash\Client\Exception\CyclicDependencyException
> if that happens.

Example:

```php
<?php

use Unleash\Client\Helper\Builder\ConfigurationAware;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\UnleashBuilder;

final class CustomMetricsHandler implements MetricsHandler, ConfigurationAware
{
    private UnleashConfiguration $configuration;

    public function setConfiguration(UnleashConfiguration $configuration): void
    {
        // this method gets called automatically by the builder
        $this->configuration = $configuration;
    }

    public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
    {
        // the configuration object is available here
        if ($this->configuration->getInstanceId() === '...') {
            // do something
        }
    }
}

$instance = UnleashBuilder::create()
    ->withMetricsHandler(new CustomMetricsHandler())
    ->build();
```

#### Required parameters

The app name, instance id and app url are required as per the specification.

```php
<?php

use Unleash\Client\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id');
```

If you're using Unleash v4 you also need to specify authorization key (API key), you can do so with custom header.

```php
<?php

use Unleash\Client\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->withHeader('Authorization', 'my-api-key');
```

To filter feature toggles by tag or name prefix you can use the `Url` helper:

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Helper\Url;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl(new Url('https://some-app-url.com', namePrefix: 'somePrefix.', tags: [
        'myTag' => 'myValue',
    ]))
    ->withInstanceId('Some instance id');
```

#### Optional parameters

Some optional parameters can be set, these include:

- http client implementation ([PSR-18](https://packagist.org/providers/psr/http-client-implementation))
- request factory implementation ([PSR-17](https://packagist.org/providers/psr/http-factory-implementation))
- cache implementation ([PSR-16](https://packagist.org/providers/psr/simple-cache-implementation))
- cache ttl
- available strategies
- http headers

The builder will attempt to load http client and request factory implementations automatically. Most implementations,
such as [guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/guzzle) or
[symfony/http-client](https://packagist.org/packages/symfony/http-client) (in combination with 
[nyholm/psr7](https://packagist.org/packages/nyholm/psr7)), will be loaded automatically. If the builder is unable to
locate a http client or request factory implementation, you will need to provide some implementation on your own.

If you use [symfony/cache](https://packagist.org/packages/symfony/cache) or
[cache/filesystem-adapter](https://packagist.org/packages/cache/filesystem-adapter) as your cache implementation, the
cache handler will be created automatically, otherwise you need to provide some implementation on your own.

```php
<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Helper\Url;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com') // as a string
    ->withAppUrl(new Url('https://some-app-url.com', tags: ['myTag' => 'myValue'])) // or as Url instance
    ->withInstanceId('Some instance id')
    // now the optional ones
    ->withHttpClient(new Client())
    ->withRequestFactory(new HttpFactory())
    ->withCacheHandler(new FilesystemCachePool( // example with using cache/filesystem-adapter
        new Filesystem(
            new Local(sys_get_temp_dir()),
        ),
    ), 30) // the second parameter is time to live in seconds
    ->withCacheTimeToLive(60) // you can also set the cache time to live separately
    // if you don't add any strategies, by default all strategies are added
    ->withStrategies( // this example includes all available strategies
        new DefaultStrategyHandler(),
        new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
        new IpAddressStrategyHandler(),
        new UserIdStrategyHandler(),
    )
    // add headers one by one, if you specify a header with the same name multiple times it will be replaced by the
    // latest value
    ->withHeader('My-Custom-Header', 'some-value')
    ->withHeader('Some-Other-Header', 'some-other-value')
    // you can specify multiple headers at the same time, be aware that this REPLACES all the headers
    ->withHeaders([
        'Yet-Another-Header' => 'and-another-value',
    ]);
```
#### Returning intermediate objects

For some use cases the builder can return intermediate objects, for example the `UnleashRepository` object. This can be 
useful if you need to directly interact with the repository, to refresh the cache manually for example.

```php 
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Helper\Url;

$repository = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl(new Url('https://some-app-url.com', namePrefix: 'somePrefix.', tags: [
        'myTag' => 'myValue',
    ]))
    ->withInstanceId('Some instance id')
    ->buildRepository();
$repository->refreshCache();
```

## Proxy SDK

By default the SDK uses the Server-side endpoints on the Unleash API. You can also use the Proxy SDK, which is a
lightweight SDK that uses the Client-side endpoints on the Unleash API. The Proxy SDK give a substantial performance improvement when using a large set of feature toggles (10K+).

To use the Proxy SDK, you need to call `withProxy($apiKey)` on the builder. The `$apiKey` needs to be a [frontend token](https://docs.getunleash.io/reference/api-tokens-and-client-keys#front-end-tokens). Note that `withProxy($apiKey)` is in lieu of setting the API key header.

Example of using the builder to create a Proxy SDK instance:

```php
<?php
$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com/api')
    ->withInstanceId('Some instance id')
    ->withProxy("some-proxy-key"); // <-- This is the only difference

$unleash = $builder->build();

$unleash.isEnabled("some-feature");
```

As of version 1.12, the Proxy SDK requires [Edge](https://docs.getunleash.io/reference/unleash-edge), so the `appUrl` needs to point to the Edge server.

Not supported in the Proxy SDK:
- Custom strategies
- Registration (this is handled by Edge)


## Caching

It would be slow to perform a http request every time you check if a feature is enabled, especially in popular
apps. That's why this library has built-in support for PSR-16 cache implementations.

If you don't provide any implementation and default implementation exists, it's used, otherwise you'll get an exception.
You can also provide a TTL which defaults to 15 seconds for standard cache and 30 minutes for stale data cache.

> Stale data cache is used when http communication fails while fetching feature list from the server. In that case
> the latest valid version is used until the TTL expires or server starts responding again. An event gets emitted
> when this happens, for more information see [events documentation](doc/events.md).

Cache implementations supported out of the box (meaning you don't need to configure anything):

- [symfony/cache](https://packagist.org/packages/symfony/cache)
- [cache/filesystem-adapter](https://packagist.org/packages/cache/filesystem-adapter)

```php
<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Unleash\Client\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withCacheHandler(new FilesystemCachePool( // example with using cache/filesystem-adapter
        new Filesystem(
            new Local(sys_get_temp_dir()),
        ),
    ))
    ->withCacheTimeToLive(120)
    ->withStaleTtl(300)
;

// you can set the cache handler explicitly to null to revert back to autodetection

$builder = $builder
    ->withCacheHandler(null);
```

You can use a different cache implementation for standard item cache and for stale cache. If you don't provide any
implementation for stale cache, the same instance as for standard cache is used.

```php
<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Unleash\Client\UnleashBuilder;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$builder = UnleashBuilder::create()
    ->withCacheHandler(new FilesystemCachePool( // example with using cache/filesystem-adapter
        new Filesystem(
            new Local(sys_get_temp_dir()),
        ),
    ))
    ->withStaleCacheHandler(new Psr16Cache(new ArrayAdapter()))
    ->withCacheTimeToLive(120)
    ->withStaleTtl(300)
;
```

## Bootstrapping

You can set a default response from the SDK in cases when for some reason contacting Unleash server fails.

By default, you can bootstrap using:

- json string
- file (via path or instance of `SplFileInfo`)
- URL address
- custom [stream wrapper](https://www.php.net/manual/en/wrappers.php) path
- `array`
- instances of `Traversable`
- instances of `JsonSerializable`

These correspond to bootstrap providers:

- `JsonBootstrapProvider` (json string)
- `FileBootstrapProvider` (file, URL address, custom stream wrapper path)
- `JsonSerializableBootstrapProvider` (array, Traversable, JsonSerializable)
- `EmptyBootstrapProvider` (default provider that doesn't provide any bootstrap)
- `CompoundBootstrapProvider` (can contain multiple bootstrap providers and tries them one by one)

Examples of bootstraps:

```php
<?php

use Unleash\Client\UnleashBuilder;

$bootstrapJson = '{"features": []}';
$bootstrapFile = 'path/to/my/file.json';
$bootstrapSplFile = new SplFileInfo('path/to/my/file.json');
$bootstrapUrl = 'https://example.com/unleash-bootstrap.json';
$bootstrapStreamWrapper = 's3://my-bucket/bootstrap.json'; // assuming you have a custom stream wrapper called 's3'
$bootstrapArray = [
    'features' => [
        [
            'enabled' => true,
            'name' => 'BootstrapDemo',
            'description' => '',
            'project' => 'default',
            'stale' => false,
            'type' => 'release',
            'variants' => [],
            'strategies' => [[ 'name' => 'default' ]],
        ],
    ],
];
$bootstrapTraversable = new class implements Iterator {
    public function current(): mixed
    {
        // todo implement method
    }
    
    public function next(): void
    {
        // todo implement method
    }
    
    public function key(): mixed
    {
        // todo implement method
    }
    
    public function valid(): bool
    {
        // todo implement method
    }
    
    public function rewind(): void
    {
        // todo implement method
    }
};
$bootstrapJsonSerializable = new class implements JsonSerializable {
    public function jsonSerialize(): array {
        // TODO: Implement jsonSerialize() method.
    }
}

// now assign them to the builder, note that each withBootstrap* method call overrides the bootstrap

$builder = UnleashBuilder::create()
    ->withBootstrap($bootstrapJson)
    ->withBootstrapFile($bootstrapFile)
    ->withBootstrapFile($bootstrapSplFile)
    ->withBootstrapUrl($bootstrapUrl)
    ->withBootstrapFile($bootstrapStreamWrapper)
    ->withBootstrap($bootstrapArray)
    ->withBootstrap($bootstrapTraversable)
    ->withBootstrap($bootstrapJsonSerializable)
    ->withBootstrap(null) // empty bootstrap
;
```

Using bootstrap providers directly:

```php
<?php

use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\Bootstrap\FileBootstrapProvider;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Client\UnleashBuilder;

// using variables defined in previous example, again each call overrides the last bootstrap provider

$builder = UnleashBuilder::create()
    ->withBootstrapProvider(new JsonBootstrapProvider($bootstrapJson))
    ->withBootstrapProvider(new FileBootstrapProvider($bootstrapFile))
    ->withBootstrapProvider(new FileBootstrapProvider($bootstrapSplFile))
    ->withBootstrapProvider(new FileBootstrapProvider($bootstrapUrl))
    ->withBootstrapProvider(new FileBootstrapProvider($bootstrapStreamWrapper))
    ->withBootstrapProvider(new JsonSerializableBootstrapProvider($bootstrapArray))
    ->withBootstrapProvider(new JsonSerializableBootstrapProvider($bootstrapTraversable))
    ->withBootstrapProvider(new JsonSerializableBootstrapProvider($bootstrapJsonSerializable))
    ->withBootstrapProvider(new EmptyBootstrapProvider()) // equivalent to ->withBootstrap(null)
;
```

Using multiple bootstrap providers:

```php
<?php

use Unleash\Client\Bootstrap\CompoundBootstrapProvider;
use Unleash\Client\Bootstrap\FileBootstrapProvider;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Client\UnleashBuilder;

// using variables defined in first example

$provider = new CompoundBootstrapProvider(
    new FileBootstrapProvider($bootstrapUrl),
    new FileBootstrapProvider($bootstrapFile),
    new JsonSerializableBootstrapProvider($bootstrapArray),
);

// All providers in compound bootstrap provider will be tried one by one in the order they were assigned until
// at least one returns something.

// If no provider returns non-null value, the compound provider itself returns null.

// If an exception is thrown in any of the inner providers it's ignored and next provider is tried.

// If an exception was thrown in any of the inner providers and no other provider returned any value, the exceptions
// from inner providers are thrown using a CompoundException, you can get the exceptions by calling ->getExceptions()
// on it.

$builder = UnleashBuilder::create()
    ->withBootstrapProvider($provider);
```

### Custom bootstrap provider

Creating a custom bootstrap provider is very simple, just implement the `BootstrapProvider` interface and use your
class in the builder:

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Bootstrap\BootstrapProvider;

final class MyBootstrapProvider implements BootstrapProvider
{
    public function getBootstrap() : array|JsonSerializable|Traversable|null
    {
        // TODO: Implement getBootstrap() method.
    }
}

$builder = UnleashBuilder::create()
    ->withBootstrapProvider(new MyBootstrapProvider());
```

### Disabling communication with Unleash server

It may be useful to disable communication with the Unleash server for local development and using a bootstrap instead.

Note that when you disable communication with Unleash and don't provide a bootstrap, an exception will be thrown.

> Tip: Set the cache interval to 0 to always have a fresh bootstrap content.

> The usually required parameters (app name, instance id, app url) are not required when communication is disabled.

```php
<?php

use Unleash\Client\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withBootstrap('{}')
    ->withFetchingEnabled(false) // here we disable communication with Unleash server
    ->withCacheTimeToLive(0) // disable the caching layer to always get a fresh bootstrap
    ->build();
```

## Strategies

Unleash servers can use multiple strategies for enabling or disabling features. Which strategy gets used is defined
on the server. This implementation supports all v4 strategies. 
[More here](https://docs.getunleash.io/user_guide/activation_strategy).

### Default strategy

This is the simplest of them and simply always returns true if the feature defines default as its chosen strategy
and doesn't need any context parameters.

### IP address strategy

Enables feature based on the IP address. Takes current user's IP address from the context object. You can provide your
own IP address or use the default (`$_SERVER['REMOTE_ADDR']`). Providing your own is especially useful if you're behind
proxy and thus `REMOTE_ADDR` would return your proxy server's IP address instead.

> As of 1.4.0 the CIDR notation is supported

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

// without context, using the auto detected IP
$enabled = $unleash->isEnabled('some-feature');

// with context
$context = new UnleashContext(ipAddress: $_SERVER['HTTP_X_FORWARDED_FOR']);
// or pre php 8 style
$context = (new UnleashContext())
    ->setIpAddress($_SERVER['HTTP_X_FORWARDED_FOR']);
$enabled = $unleash->isEnabled('some-feature', $context);
```

### User ID strategy

Enables feature based on the user ID. The user ID can be any string. You must always provide your own user id via 
context.

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

$context = new UnleashContext(currentUserId: 'some-user-id');
$enabled = $unleash->isEnabled('some-feature', $context);
```

### Gradual rollout strategy

Also known as flexible rollout. Allows you to enable feature for only a percentage of users based on their user id,
session id or randomly. The default is to try in this order: user id, session id, random.

If you specify the user id type on your Unleash server, you must also provide the user id via context, same as in the
User ID strategy. Session ID can also be provided via context, it defaults to the current session id via `session_id()`
call.

> This strategy requires a stickiness calculator that transforms the id (user, session or random) into a number between
> 1 and 100. You can provide your own or use the default \Unleash\Client\Stickiness\MurmurHashCalculator

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

// assume the feature uses the default type which means that it will default to either session id (if session is started)
// or randomly
$unleash->isEnabled('some-feature');

// still using the default strategy but this time with user id (which is the first to be used if present)
$context = new UnleashContext(currentUserId: 'some-user-id');
$unleash->isEnabled('some-feature', $context);

// let's start the session to ensure the session id is used
session_start();
$unleash->isEnabled('some-feature');

// or you can provide your own session id
$context = new UnleashContext(sessionId: 'sess-123456');
$unleash->isEnabled('some-feature', $context);

// assume the feature is set to use the user id, the first call returns false (no context given), the second
// one returns true/false based on the user id
$unleash->isEnabled('some-feature');
$context = new UnleashContext(currentUserId: 'some-user-id');
$unleash->isEnabled('some-feature', $context);

// the same goes for session, assume the session isn't started yet and the feature is set to use the session type
$unleash->isEnabled('some-feature'); // returns false because no session is available

$context = new UnleashContext(sessionId: 'some-session-id');
$unleash->isEnabled('some-feature', $context); // works because you provided the session id manually

session_start();
$unleash->isEnabled('some-feature'); // works because the session is started

// lastly you can force the feature to use the random type which always works
$unleash->isEnabled('some-feature');
```

### Hostname strategy

This strategy allows you to match against a list of server hostnames (which are not the same as http hostnames).

If you don't specify a hostname in context, it defaults to the current hostname using 
[`gethostname()`](https://www.php.net/gethostname).

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

// context with custom hostname
$context = new UnleashContext(hostname: 'My-Cool-Hostname');
$enabled = $unleash->isEnabled('some-feature', $context);

// without custom hostname, defaults to gethostname() result or null
$enabled = $unleash->isEnabled('some-feature');
```

> Note: This library also implements some deprecated strategies, namely `gradualRolloutRandom`, `gradualRolloutSessionId`
> and `gradualRolloutUserId` which all alias to the Gradual rollout strategy.

### Context provider

Manually creating relevant context can get tiring real fast. Luckily you can create your own context provider that
will do it for you!

```php
<?php

use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\UnleashBuilder;

final class MyContextProvider implements UnleashContextProvider 
{
    public function getContext(): Context
    {
        $context = new UnleashContext();
        $context->setCurrentUserId('user id from my app');
        
        return $context;     
    }
}

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    // here we set the custom provider
    ->withContextProvider(new MyContextProvider())
    ->build();

if ($unleash->isEnabled('someFeature')) { // this call will use your context provider with the provided user id

}
```

### Custom strategies

To implement your own strategy you need to create a class implementing `StrategyHandler` (or `AbstractStrategyHandler`
which contains some useful methods). Then you need to instruct the builder to use your custom strategy.

```php
<?php

use Unleash\Client\Strategy\AbstractStrategyHandler;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Strategy\DefaultStrategyHandler;

class AprilFoolsStrategy extends AbstractStrategyHandler
{
    public function __construct(private DefaultStrategyHandler $original)
    {
    }
    
    public function getStrategyName() : string
    {
        return 'aprilFools';
    }
    
    public function isEnabled(Strategy $strategy, Context $context) : bool
    {
        $date = new DateTimeImmutable();
        if ((int) $date->format('n') === 4 && (int) $date->format('j') === 1) {
            return (bool) random_int(0, 1);
        }
        
        return $this->original->isEnabled($strategy, $context);
    }
}
```

Now you must instruct the builder to use your new strategy

```php
<?php

use Unleash\Client\UnleashBuilder;
use Unleash\Client\Strategy\IpAddressStrategyHandler;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->withStrategy(new AprilFoolsStrategy()) // this will append your strategy to the existing list
    ->build();

// if you want to replace all strategies, use withStrategies() instead

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->withStrategies(new AprilFoolsStrategy(), new IpAddressStrategyHandler())
    // now the unleash object will have only the two strategies
    ->build();
```

## Variants

You can use multiple variants of one feature, for example for A/B testing. If no variant matches or the feature doesn't
have any variants, a default one will be returned which returns `false` for `isEnabled()`. You can also provide your
own default variant.

Variant may or may not contain a payload.

```php
<?php

use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\Enum\VariantPayloadType;
use Unleash\Client\DTO\DefaultVariantPayload;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();
    
$variant = $unleash->getVariant('nonexistentFeature');
assert($variant->isEnabled() === false);

// getVariant() does isEnabled() call in the background meaning that it will return the default falsy variant
// whenever isEnabled() returns false
$variant = $unleash->getVariant('existingFeatureThatThisUserDoesNotHaveAccessTo');
assert($variant->isEnabled() === false);

$variant = $unleash->getVariant('someFeature', new UnleashContext(currentUserId: '123'));
if ($variant->isEnabled()) {
    $payload = $variant->getPayload();
    if ($payload !== null) {
        if ($payload->getType() === VariantPayloadType::JSON) {
            $jsonData = $payload->fromJson();
        }
        $stringPayload = $payload->getValue();
    }
}

// providing custom default variant

$variant = $unleash->getVariant('nonexistentFeature', fallbackVariant: new DefaultVariant(
    'variantName',
    enabled: true,
    payload: new DefaultVariantPayload(VariantPayloadType::STRING, 'somePayload'),
));
assert($variant->getPayload()->getValue() === 'somePayload');
```

## Client registration

By default, the library automatically registers itself as an application in the Unleash server. If you want to prevent
this, use `withAutomaticRegistrationEnabled(false)` in the builder.

```php
<?php

use Unleash\Client\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some App Name')
    ->withAppUrl('https://somewhere.com')
    ->withInstanceId('some-instance-id')
    ->withAutomaticRegistrationEnabled(false)
    ->build();

// event though the client will not attempt to register, you can still use isEnabled()
$unleash->isEnabled('someFeature');

// if you want to register manually
$unleash->register();

// you can call the register method multiple times, the Unleash server doesn't mind
$unleash->register();
$unleash->register();
```

## Metrics

By default, this library sends metrics which are simple statistics about whether user was granted access or not.

The metrics will be bundled and sent once the bundle created time crosses the configured threshold.
By default this threshold is 30,000 milliseconds (30 seconds) meaning that when a new bundle gets created it won't be
sent sooner than in 30 seconds. That doesn't mean it's guaranteed that the metrics will be sent every 30 seconds, it
only guarantees that the metrics won't be sent sooner.

Example:

1. user visits your site and this sdk gets triggered, no metric has been sent
2. after five seconds user visits another page where again this sdk gets triggered, no metric sent
3. user waits one minute before doing anything, no one else is accessing your site
4. after one minute user visits another page, the metrics have been sent to the Unleash server

In the example above the metric bundle gets sent after 1 minute and 5 seconds because there was no one to trigger
the code.

```php
<?php

use Unleash\Client\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some App Name')
    ->withAppUrl('https://somewhere.com')
    ->withInstanceId('some-instance-id')
    ->withMetricsEnabled(false) // turn off metric sending
    ->withMetricsEnabled(true) // turn on metric sending
    ->withMetricsInterval(60_000) // interval in milliseconds (60 seconds)
    ->withMetricsCacheHandler(new Psr16Cache(new RedisAdapter())) // use custom cache handler for metrics, defaults to standard cache handler
    ->build();

// the metric will be collected but not sent immediately
$unleash->isEnabled('test');
sleep(10);
// now the metrics will get sent
$unleash->isEnabled('test');
```

## Custom headers via middleware

While middlewares for http client are not natively supported by this SDK, you can pass your own http client which 
supports them.

The most popular http client, guzzle, supports them out of the box and here's an example of how to pass custom headers
automatically (for more information visit [official guzzle documentation on middlewares](https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html)):

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Unleash\Client\UnleashBuilder;

// any callable is valid, it may be a function reference, anonymous function or an invokable class

// example invokable class
final class AddHeaderMiddleware
{
    public function __construct(
        private readonly string $headerName,
        private readonly string $value,
    ) {
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request->withHeader($this->headerName, $this->value);
    }
}

// example anonymous function
$addHeaderMiddleware = fn (string $headerName, string $headerValue)
    => fn(RequestInterface $request)
        => $request->withHeader($headerName, $headerValue);

// create a handler stack that holds information about all middlewares
$stack = HandlerStack::create(new CurlHandler());
// mapRequest is a helper that simplifies modifying request
$stack->push(Middleware::mapRequest(new AddHeaderMiddleware('X-My-Header', 'Some-Value')));
// or with lambda
$stack->push(Middleware::mapRequest($addHeaderMiddleware('X-My-Header2', 'Some-Value')));
// assign the stack with middlewares as a handler
$httpClient = new Client(['handler' => $stack]);

$unleash = UnleashBuilder::create()
    ->withHttpClient($httpClient) // assign the custom http client
    ->withAppName('My-App')
    ->withInstanceId('My-Instance')
    ->withAppUrl('http://localhost:4242')
    ->build();

// now every http request will have X-My-Header header with value Some-Value
$unleash->isEnabled('some-feature');
```

## Constraints

Constraints are supported by this SDK and will be handled correctly by `Unleash::isEnabled()` if present.

## GitLab specifics

- In GitLab you have to use the provided instance id, you cannot create your own.
- No authorization header is necessary.
- Instead of app name you need to specify the GitLab environment.
  - For this purpose you can use `withGitlabEnvironment()` method in builder, it's an alias to `withAppName()` but
    communicates the intent better.
- GitLab doesn't use registration system, you can set the SDK to disable automatic registration and save one http call.
- GitLab doesn't read metrics, you can set the SDK to disable sending them and save some http calls.

```php
<?php

use Unleash\Client\UnleashBuilder;

$gitlabUnleash = UnleashBuilder::createForGitlab()
    ->withInstanceId('H9sU9yVHVAiWFiLsH2Mo') // generated in GitLab
    ->withAppUrl('https://git.example.com/api/v4/feature_flags/unleash/1')
    ->withGitlabEnvironment('Production')
    ->build();

// the above is equivalent to
$gitlabUnleash = UnleashBuilder::create()
    ->withInstanceId('H9sU9yVHVAiWFiLsH2Mo')
    ->withAppUrl('https://git.example.com/api/v4/feature_flags/unleash/1')
    ->withGitlabEnvironment('Production')
    ->withAutomaticRegistrationEnabled(false)
    ->withMetricsEnabled(false)
    ->build();
```

Check out our guide for more information on how to build and scale [feature flag systems](https://docs.getunleash.io/topics/feature-flags/feature-flag-best-practices)
