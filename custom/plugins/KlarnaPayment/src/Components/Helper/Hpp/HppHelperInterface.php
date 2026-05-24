<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\Hpp;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

interface HppHelperInterface
{
    public function handlePayment(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse;

    public function isHpp(Request $request): bool;
}