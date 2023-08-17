<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Product\Images\ThumbnailService;

use League\Flysystem\FilesystemOperator;

/**
 * @deprecated
 */
interface ThumbnailServiceInterface
{
    public function make(FilesystemOperator $filesystem, string $filename, $data): void;
}
