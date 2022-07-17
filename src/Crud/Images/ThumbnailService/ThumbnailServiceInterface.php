<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService;

use Enjoys\Upload\File;

interface ThumbnailServiceInterface
{
    public function make(File $file): void;
}
