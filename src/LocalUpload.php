<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

final class LocalUpload implements FileSystemAdapterInterface
{
    public function __construct(private string $rootDirectory)
    {
    }

    public function getFileSystem(): FilesystemOperator
    {
        return new Filesystem(
            new LocalFilesystemAdapter(
                $_ENV['PROJECT_DIR'] . $this->rootDirectory,
                PortableVisibilityConverter::fromArray([
                    'dir' => [
                        'private' => 0755,
                    ],
                ]),
            )
        );
    }

    public function getFullPath(string $relativePath): string
    {
        return $_ENV['PROJECT_DIR'] . $this->rootDirectory . $relativePath;
    }

}
