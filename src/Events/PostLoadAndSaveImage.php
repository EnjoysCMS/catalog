<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use Enjoys\Upload\UploadProcessing;
use League\Flysystem\Filesystem;
use Symfony\Contracts\EventDispatcher\Event;

final class PostLoadAndSaveImage extends Event
{
    public function __construct(private string $location, private Filesystem $filesystem)
    {
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }


}
