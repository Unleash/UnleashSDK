<?php

namespace Unleash\Client\Enum;

use JetBrains\PhpStorm\Deprecated;

final class ConstraintOperator
{
    // legacy
    /**
     * @var string
     */
    #[Deprecated('Please use IN_LIST constant')]
    public const IN = self::IN_LIST;

    /**
     * @var string
     */
    #[Deprecated('Please use NOT_IN_LIST constant')]
    public const NOT_IN = self::NOT_IN_LIST;

    // list
    /**
     * @var string
     */
    public const IN_LIST = 'IN';

    /**
     * @var string
     */
    public const NOT_IN_LIST = 'NOT_IN';

    // string
    /**
     * @var string
     */
    public const STRING_STARTS_WITH = 'STR_STARTS_WITH';

    /**
     * @var string
     */
    public const STRING_ENDS_WITH = 'STR_ENDS_WITH';

    /**
     * @var string
     */
    public const STRING_CONTAINS = 'STR_CONTAINS';

    // number
    /**
     * @var string
     */
    public const NUMBER_EQUALS = 'NUM_EQ';

    /**
     * @var string
     */
    public const NUMBER_GREATER_THAN = 'NUM_GT';

    /**
     * @var string
     */
    public const NUMBER_GREATER_THAN_OR_EQUALS = 'NUM_GTE';

    /**
     * @var string
     */
    public const NUMBER_LOWER_THAN = 'NUM_LT';

    /**
     * @var string
     */
    public const NUMBER_LOWER_THAN_OR_EQUALS = 'NUM_LTE';

    // date
    /**
     * @var string
     */
    public const DATE_AFTER = 'DATE_AFTER';

    /**
     * @var string
     */
    public const DATE_BEFORE = 'DATE_BEFORE';

    // versions
    /**
     * @var string
     */
    public const VERSION_EQUALS = 'SEMVER_EQ';

    /**
     * @var string
     */
    public const VERSION_GREATER_THAN = 'SEMVER_GT';

    /**
     * @var string
     */
    public const VERSION_LOWER_THAN = 'SEMVER_LT';
}
