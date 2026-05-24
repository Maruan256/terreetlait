<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Validator;

use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

class CartHashValidator extends AbstractComparisonValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getErrorCode(): ?string
    {
        return CartHash::NOT_EQUAL_ERROR;
    }

    /**
     * Compares the two given values to find if their relationship is valid.
     */
    protected function compareValues(mixed $value1, mixed $value2): bool
    {
        return $value1 == $value2;
    }
}
