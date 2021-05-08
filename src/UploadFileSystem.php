<?php

namespace EnjoysCMS\Module\Catalog;


use InvalidArgumentException;
use RuntimeException;
use Upload\Exception\UploadException;
use Upload\File;
use Upload\Storage\Base;

class UploadFileSystem extends Base
{
    /**
     * Upload directory
     * @var string
     */
    protected $directory;

    private string $fullPathFileNameWithExtension;

    /**
     * Overwrite existing files?
     * @var bool
     */
    protected $overwrite;

    /**
     * Constructor
     * @param  string                       $directory      Relative or absolute path to upload directory
     * @param  bool                         $overwrite      Should this overwrite existing files?
     * @throws InvalidArgumentException                    If directory does not exist
     * @throws InvalidArgumentException                    If directory is not writable
     */
    public function __construct($directory, $overwrite = false)
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException('Directory does not exist');
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException('Directory is not writable');
        }
        $this->directory = rtrim($directory, '/') . DIRECTORY_SEPARATOR;
        $this->overwrite = $overwrite;
    }

    /**
     * Upload
     * @param  File $file The file object to upload
     * @param  string $newName Give the file it a new name
     * @return bool
     * @throws RuntimeException   If overwrite is false and file already exists
     */
    public function upload(File $file, $newName = null)
    {
        if (is_string($newName)) {
            $fileName = strpos($newName, '.') ? $newName : $newName.'.'.$file->getExtension();

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
        $this->makeDirectory($directory);

        return $this->moveUploadedFile($file->getPathname(), $newFile);
    }

    /**
     * Move uploaded file
     *
     * This method allows us to stub this method in unit tests to avoid
     * hard dependency on the `move_uploaded_file` function.
     *
     * @param  string $source      The source file
     * @param  string $destination The destination file
     * @return bool
     */
    protected function moveUploadedFile($source, $destination)
    {
        return move_uploaded_file($source, $destination);
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    private function makeDirectory(string $directory)
    {


        if (preg_match("/(\/\.+|\.+)$/i", $directory)) {
            throw new \Exception(
                sprintf("Нельзя создать директорию: %s", $directory)
            );
        }

        //Clear the most recent error
        error_clear_last();

        if (!is_dir($directory)) {
            if (@mkdir($directory, 0777, true) === false) {
                /** @var string[] $error */
                $error = error_get_last();
                throw new \Exception(
                    sprintf("Не удалось создать директорию: %s! Причина: %s", $directory, $error['message'])
                );
            }

        }
    }

    /**
     * @return string
     */
    public function getFullPathFileNameWithExtension(): string
    {
        return $this->fullPathFileNameWithExtension;
    }

    /**
     * @param string $fullPathFileNameWithExtension
     */
    private function setFullPathFileNameWithExtension(string $fullPathFileNameWithExtension): void
    {
        $this->fullPathFileNameWithExtension = $fullPathFileNameWithExtension;
    }
}
