<?php

namespace Unleash\Client\Helper;

use Unleash\Client\Exception\InvalidIpAddressException;

/**
 * @internal
 */
final class NetworkCalculator
{
    public function __construct(
        private readonly string $ipAddress,
        private readonly int $networkSize
    ) {
    }

    public static function fromString(string $ipAddressAndNetwork): self
    {
        $regex = '@([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(?:/([0-9]{1,2}))?@';
        if (!preg_match($regex, $ipAddressAndNetwork, $matches)) {
            throw new InvalidIpAddressException("{$ipAddressAndNetwork} is not a valid IP or CIDR block");
        }

        $ipAddress = $matches[1];
        $networkSize = $matches[2] ?? 32;

        return new self($ipAddress, (int) $networkSize);
    }

    public function isInRange(string $ipAddress): bool
    {
        return substr_compare(
            sprintf('%032b', ip2long($ipAddress)),
            sprintf('%032b', ip2long($this->ipAddress)),
            0,
            $this->networkSize,
        ) === 0;
    }
}
