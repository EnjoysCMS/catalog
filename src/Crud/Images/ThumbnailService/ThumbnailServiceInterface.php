<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService;

use League\Flysystem\FilesystemOperator;

interface ThumbnailServiceInterface
{
    public function make(FilesystemOperator $filesystem, string $filename, $data): void;
}
