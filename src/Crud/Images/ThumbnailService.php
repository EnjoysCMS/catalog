<?php

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use League\Flysystem\FilesystemOperator;

interface ThumbnailService
{
    public function make(string $location, FilesystemOperator $filesystem): void;
}
