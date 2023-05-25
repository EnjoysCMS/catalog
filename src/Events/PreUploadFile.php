<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use Enjoys\Upload\UploadProcessing;
use Symfony\Contracts\EventDispatcher\Event;

final class PreUploadFile extends Event
{
    public function __construct(private UploadProcessing $file)
    {
    }

    public function getFile(): UploadProcessing
    {
        return $this->file;
    }
}
