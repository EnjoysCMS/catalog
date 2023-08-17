<?php

namespace EnjoysCMS\Module\Catalog\Admin\Product\Images;

use League\Flysystem\FilesystemOperator;

interface ThumbnailService
{
    public function make(string $location, FilesystemOperator $filesystem): void;
}
