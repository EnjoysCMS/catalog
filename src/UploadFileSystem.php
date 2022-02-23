<?php

namespace EnjoysCMS\Module\Catalog;


use InvalidArgumentException;
use Upload\Exception\UploadException;
use Upload\File;
use Upload\Storage\Base;

use function Enjoys\FileSystem\createDirectory;

class UploadFileSystem extends Base
{

    protected string $directory;
    private string $fullPathFileNameWithExtension;
    protected bool $overwrite;

    /**
     * @throws \Exception
     */
    public function __construct(string $directory, bool $overwrite = false)
    {
        createDirectory($directory);

        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('Directory is not writable: %s', $directory));
        }
        $this->directory = rtrim($directory, '/') . DIRECTORY_SEPARATOR;
        $this->overwrite = $overwrite;
    }


    /**
     * @throws \Exception
     */
    public function upload(File $file, $newName = null): bool
    {
        if (is_string($newName)) {
            $fileName = strpos($newName, '.') ? $newName : $newName . '.' . $file->getExtension();
        } else {
            $fileName = $file->getNameWithExtension();
        }

        $newFile = $this->directory . $fileName;
        if ($this->overwrite === false && file_exists($newFile)) {
            $file->addError('File already exists');
            throw new UploadException('File already exists');
        }

        $this->setFullPathFileNameWithExtension($newFile);

        $directory = pathinfo($newFile, PATHINFO_DIRNAME);

        createDirectory($directory);

        return $this->moveUploadedFile($file->getPathname(), $newFile);
    }

    protected function moveUploadedFile(string $source, string $destination): bool
    {
        return move_uploaded_file($source, $destination);
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }


    public function getFullPathFileNameWithExtension(): string
    {
        return $this->fullPathFileNameWithExtension;
    }

    private function setFullPathFileNameWithExtension(string $fullPathFileNameWithExtension): void
    {
        $this->fullPathFileNameWithExtension = $fullPathFileNameWithExtension;
    }
}
