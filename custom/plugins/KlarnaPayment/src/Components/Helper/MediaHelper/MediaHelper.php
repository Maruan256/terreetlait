<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\MediaHelper;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class MediaHelper implements MediaHelperInterface
{
    public function createNewMedia(string $filepath = ''): MediaEntity
    {
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        $filename = pathinfo($filepath, PATHINFO_FILENAME);

        $media = new MediaEntity();
        $media->setId(Uuid::randomHex());
        $media->setUrl($filepath);
        $media->setMimeType('image/svg+xml');
        $media->setFileExtension($ext);
        $media->setFilename($filename);
        $media->setPrivate(true);
        $media->setCreatedAt(new \DateTime());

        return $media;
    }
}
