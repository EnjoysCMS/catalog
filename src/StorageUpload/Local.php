<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\StorageUpload;


use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

final class Local implements StorageUploadInterface
{
    private string $rootDirectory;

    public function __construct(string $rootDirectory, private array $permissionMap = [])
    {
        $this->rootDirectory = $_ENV['PROJECT_DIR'] . rtrim($rootDirectory, '/') . '/';
    }

    public function getFileSystem(): FilesystemOperator
    {
        return new Filesystem(
            new LocalFilesystemAdapter(
                $this->rootDirectory,
                PortableVisibilityConverter::fromArray(
                    $this->permissionMap
                ),
            )
        );
    }

    public function getFullPath(string $relativePath): string
    {
        return $this->rootDirectory . $relativePath;
    }
}
