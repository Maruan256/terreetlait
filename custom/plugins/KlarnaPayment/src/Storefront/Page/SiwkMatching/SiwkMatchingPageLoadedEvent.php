<?php declare(strict_types=1);

namespace KlarnaPayment\Storefront\Page\SiwkMatching;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class SiwkMatchingPageLoadedEvent extends PageLoadedEvent
{
    protected SiwkMatchingPage $page;

    public function __construct(SiwkMatchingPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): SiwkMatchingPage
    {
        return $this->page;
    }
}