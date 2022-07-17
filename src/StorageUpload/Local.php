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
    private string $publicUrl;

    public function __construct(string $rootDirectory, string $publicUrl, private array $permissionMap = [])
    {
        $this->rootDirectory = $_ENV['PROJECT_DIR'] . rtrim($rootDirectory, '/') . '/';
        $this->publicUrl = rtrim($publicUrl, '/') . '/';
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

    public function getUrl(string $path): string
    {
        return $this->publicUrl . $path;
    }
}
