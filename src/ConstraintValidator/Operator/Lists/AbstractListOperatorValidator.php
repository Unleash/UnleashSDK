<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

use Override;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractListOperatorValidator extends AbstractOperatorValidator
{
    #[Override]
    protected function acceptsValues(array|string $values): bool
    {
        $arrayIsListFunction = function (array $array) : bool {
            if (function_exists('array_is_list')) {
                return array_is_list($array);
            }
            if ($array === []) {
                return true;
            }
            $current_key = 0;
            foreach ($array as $key => $noop) {
                if ($key !== $current_key) {
                    return false;
                }
                ++$current_key;
            }
            return true;
        };
        return is_array($values) && $arrayIsListFunction($values);
    }
}
