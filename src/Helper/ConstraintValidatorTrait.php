<?php

namespace Unleash\Client\Helper;

use Unleash\Client\ConstraintValidator\ConstraintValidator;
use Unleash\Client\ConstraintValidator\DefaultConstraintValidator;

trait ConstraintValidatorTrait
{
    /**
     * @var \Unleash\Client\ConstraintValidator\ConstraintValidator|null
     */
    private $validator;

    private function getValidator(): ConstraintValidator
    {
        $this->validator = $this->validator ?? new DefaultConstraintValidator();

        return $this->validator;
    }
}
