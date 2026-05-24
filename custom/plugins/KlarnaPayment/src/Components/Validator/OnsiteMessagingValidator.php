<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Validator;

class OnsiteMessagingValidator
{
    public function isValid(bool $isActive, string $snippet, string $script): bool
    {
        if (!$isActive) {
            return false;
        }

        $onsiteMessagingSnippet = trim($snippet);
        $onsiteMessagingScript  = trim($script);

        if (!$onsiteMessagingSnippet || !$onsiteMessagingScript) {
            return false;
        }

        if (strpos($onsiteMessagingScript, '<script') === false || strpos(
            $onsiteMessagingSnippet,
            '<klarna-placement'
        ) === false) {
            return false;
        }

        return true;
    }
}
