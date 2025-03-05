<?php

namespace Unleash\Client\Enum;

final class ContextField
{
    /**
     * @var string
     */
    public const USER_ID = 'userId';

    /**
     * @var string
     */
    public const SESSION_ID = 'sessionId';

    /**
     * @var string
     */
    public const IP_ADDRESS = 'remoteAddress';

    /**
     * @var string
     */
    public const ENVIRONMENT = 'environment';

    /**
     * @var string
     */
    public const REMOTE_ADDRESS = self::IP_ADDRESS;

    /**
     * @var string
     */
    public const HOSTNAME = 'hostname';

    /**
     * @var string
     */
    public const CURRENT_TIME = 'currentTime';
}
