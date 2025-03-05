<?php

namespace Unleash\Client\Helper;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * @internal
 */
final class DefaultImplementationLocator
{
    /**
     * @var array<string,string[]>
     */
    private array $supportedPackages = [
        'cache' => [
            'symfony/cache',
            'cache/filesystem-adapter',
        ],
    ];

    /**
     * @var array<string,array<string,array<mixed>>>
     */
    private array $defaultImplementations = [
        'cache' => [
            FilesystemCachePool::class => [
                Filesystem::class => [
                    Local::class => ['{{tmpDir}}/unleash-default-cache'],
                ],
            ],
            Psr16Cache::class => [
                FilesystemAdapter::class => [
                    '',
                    0,
                    '{{tmpDir}}/unleash-default-cache',
                ],
            ],
        ],
    ];

    public function findHttpClient(): ?ClientInterface
    {
        try {
            /**
             * Discovery triggers an error if symfony/http-client is installed
             * and php-http/httplug is not, even if the intention is to find a
             * PSR-18 client. Since the discovery will otherwise be successful,
             * let's silence the error.
             */
            return @Psr18ClientDiscovery::find();
        } catch (NotFoundException $exception) {
            return null;
        }
    }

    public function findRequestFactory(): ?RequestFactoryInterface
    {
        try {
            return Psr17FactoryDiscovery::findRequestFactory();
            // @codeCoverageIgnoreStart
        } catch (NotFoundException $exception) {
            /**
             * This will only be thrown if a HTTP client was found, but a request factory is not.
             * Due to how php-http/discovery works, this scenario is unlikely to happen.
             * See linked comment for more info.
             *
             * https://github.com/Unleash/unleash-client-php/pull/27#issuecomment-920764416
             */
            return null;
            // @codeCoverageIgnoreEnd
        }
    }

    public function findCache(): ?CacheInterface
    {
        foreach ($this->defaultImplementations['cache'] as $class => $parameters) {
            if (class_exists($class)) {
                $result = $this->constructObject($class, $parameters);
                if (!$result instanceof CacheInterface) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException(
                        'The resulting object is not an instance of ' . CacheInterface::class
                    );
                    // @codeCoverageIgnoreEnd
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getCachePackages(): array
    {
        return $this->supportedPackages['cache'];
    }

    /**
     * @param class-string            $class
     * @param array<int|string,mixed> $parameters
     */
    private function constructObject(string $class, array $parameters): object
    {
        if (!class_exists($class)) {
            // @codeCoverageIgnoreStart
            throw new LogicException("The class '{$class}' does not exist");
            // @codeCoverageIgnoreEnd
        }

        $resolvedParameters = [];
        foreach ($parameters as $parameter => $value) {
            if (is_string($parameter)) {
                if (!class_exists($parameter)) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException("Unsupported string parameter that is not a class: '{$parameter}'");
                    // @codeCoverageIgnoreEnd
                }
                if (!is_array($value)) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException('Class arguments must be an array');
                    // @codeCoverageIgnoreEnd
                }
                $resolvedParameters[] = $this->constructObject($parameter, $value);
            } else {
                if (is_string($value) && strpos($value, '{{tmpDir}}') !== false) {
                    $value = str_replace('{{tmpDir}}', sys_get_temp_dir(), $value);
                }
                $resolvedParameters[] = $value;
            }
        }

        return new $class(...$resolvedParameters);
    }
}
