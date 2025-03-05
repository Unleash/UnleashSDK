<?php

namespace Unleash\Client\Strategy;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Exception\InvalidIpAddressException;
use Unleash\Client\Exception\MissingArgumentException;
use Unleash\Client\Helper\NetworkCalculator;

final class IpAddressStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @throws MissingArgumentException
     */
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$ipAddresses = $this->findParameter('IPs', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'IPs' config");
        }
        $ipAddresses = array_map('trim', explode(',', $ipAddresses));
        $enabled = false;
        $currentIpAddress = $context->getIpAddress();
        if ($currentIpAddress !== null) {
            foreach ($ipAddresses as $ipAddress) {
                try {
                    $calculator = NetworkCalculator::fromString($ipAddress);
                } catch (InvalidIpAddressException $exception) {
                    continue;
                }
                if ($calculator->isInRange($currentIpAddress)) {
                    $enabled = true;
                    break;
                }
            }
        }
        if (!$enabled) {
            return false;
        }
        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }
        return true;
    }

    public function getStrategyName(): string
    {
        return 'remoteAddress';
    }
}
