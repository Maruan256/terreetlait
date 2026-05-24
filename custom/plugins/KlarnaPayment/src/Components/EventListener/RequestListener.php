<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use Shopware\Core\PlatformRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'addStoreApiToken'
        ];
    }

    public function addStoreApiToken(RequestEvent $event): void
    {
        /** @var Request $request */
        $request = $event->getRequest();
        if( !empty($request) &&
            $request->query->has(PlatformRequest::HEADER_ACCESS_KEY) &&
            !$request->headers->has(PlatformRequest::HEADER_ACCESS_KEY))
        {
            $request->headers->set(PlatformRequest::HEADER_ACCESS_KEY, $request->query->getString(PlatformRequest::HEADER_ACCESS_KEY));
        }
    }
}