<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\CookieProvider;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CookieProvider implements CookieProviderInterface
{
    private const requiredCookies = [
        [
            'snippet_name' => 'KlarnaPayment.cookie.klarna',
            'cookie'       => 'klarna',
        ],
        [
            'snippet_name' => 'KlarnaPayment.cookie.metrix',
            'cookie'       => 'thx_',
        ],
    ];

    /** @var CookieProviderInterface */
    private $parentProvider;

    public function __construct(CookieProviderInterface $parentProvider)
    {
        $this->parentProvider = $parentProvider;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getCookieGroups(): array
    {
        $groups = $this->parentProvider->getCookieGroups();
        $key    = $this->getRequiredGroupKey($groups);

        $groups[$key]['entries'] = array_merge($groups[$key]['entries'], self::requiredCookies);

        return $groups;
    }

    private function getRequiredGroupKey(array $groups): int
    {
        foreach ($groups as $key => $group) {
            if (isset($group['isRequired']) && $group['isRequired'] === true) {
                return $key;
            }
        }

        return 0;
    }
}
