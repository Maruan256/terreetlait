<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\MediaHelper;

use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Media\MediaEntity;

interface MediaHelperInterface
{
    public function createNewMedia(string $url = ''): MediaEntity;
}
