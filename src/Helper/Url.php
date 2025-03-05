<?php

namespace Unleash\Client\Helper;

use Override;
use Stringable;

final class Url implements Stringable
{
    /**
     * @param array<string>|null $tags
     */
    public function __construct(
        private string $url,
        private ?string $namePrefix = null,
        private ?array $tags = null,
    ) {
    }

    #[Override]
    public function __toString(): string
    {
        $query = parse_url($this->url, PHP_URL_QUERY);

        $url = $this->url;

        if ($this->namePrefix !== null || $this->tags !== null) {
            $url .= $query ? '&' : '?';
        }

        if ($this->namePrefix !== null && $this->namePrefix !== '') {
            $url .= sprintf('namePrefix=%s&', urlencode($this->namePrefix));
        }

        if ($this->tags !== null) {
            foreach ($this->tags as $name => $value) {
                $url .= sprintf('tag=%s&', urlencode("{$name}:{$value}"));
            }
        }

        if (str_ends_with($url, '&') || str_ends_with($url, '?')) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    public static function appendPath(string $url, string $path): self
    {
        if (!$path) {
            return new self($url);
        }

        $parts = parse_url($url);
        assert(is_array($parts));

        if (!str_starts_with($path, '/')) {
            $path = "/{$path}";
        }
        $parts['path'] ??= '';
        if (str_ends_with($parts['path'], '/')) {
            $parts['path'] = substr($parts['path'], 0, -1);
        }

        $parts['path'] .= $path;

        return self::buildUrl($parts);
    }

    /**
     * @param array<string, mixed> $parts
     */
    public static function buildUrl(array $parts): self
    {
        $result = '';
        if (isset($parts['scheme']) && is_string($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }
        if (isset($parts['user']) && is_string($parts['user'])) {
            $result .= $parts['user'];
            if (isset($parts['pass']) && is_string($parts['pass'])) {
                $result .= ':' . $parts['pass'];
            }
            $result .= '@';
        }
        if (isset($parts['host']) && is_string($parts['host'])) {
            $result .= $parts['host'];
        }
        if (isset($parts['port']) && is_numeric($parts['port'])) {
            $result .= ':' . $parts['port'];
        }
        if (isset($parts['path']) && is_string($parts['path'])) {
            $result .= $parts['path'];
        }
        if (isset($parts['query']) && is_string($parts['query'])) {
            $result .= '?' . $parts['query'];
        }

        return new self($result);
    }
}
