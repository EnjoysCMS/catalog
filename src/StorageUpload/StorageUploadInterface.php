<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\StorageUpload;


use League\Flysystem\FilesystemOperator;

interface StorageUploadInterface
{
    public function getFileSystem(): FilesystemOperator;
    public function getFullPath(string $relativePath): string;
}
