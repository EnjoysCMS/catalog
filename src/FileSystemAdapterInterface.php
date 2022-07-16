<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use League\Flysystem\FilesystemOperator;

interface FileSystemAdapterInterface
{
    public function getFileSystem(): FilesystemOperator;
    public function getFullPath(string $relativePath): string;
}
