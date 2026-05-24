<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Validator;

use Symfony\Component\Validator\Constraints\AbstractComparison;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CartHash extends AbstractComparison
{
    public const NOT_EQUAL_ERROR = '166104af-9aaa-448b-81aa-16ce25eb886a';

    /** @var string */
    public string $message = 'Please restart the payment process.';

    /** @var array<string,string> */
    protected static $errorNames = [
        self::NOT_EQUAL_ERROR => 'KLARNA_INVALID_CART_HASH',
    ];

    /** @var array<string,string> */
    protected const ERROR_NAMES = [
        self::NOT_EQUAL_ERROR => 'KLARNA_INVALID_CART_HASH',
    ];
}
