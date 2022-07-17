<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService;


use Enjoys\Upload\File;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

final class DefaultThumbnailService implements ThumbnailServiceInterface
{

    /**
     * @throws FilesystemException
     */
    public function make(File $file): void
    {
        $data = $file->getUploadedFile()->getStream()->getContents();

        $this->save(
            str_replace(
                $file->getExtensionWithDot(),
                '_small' . $file->getExtensionWithDot(),
                $file->getTargetPath()
            ),
            $this->generate($data, [
                'resize' => [
                    300,
                    300,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                ]
            ]),
            $file->getFilesystem()
        );

        $this->save(
            str_replace(
                $file->getExtensionWithDot(),
                '_large' . $file->getExtensionWithDot(),
                $file->getTargetPath()
            ),
            $this->generate($data, [
                'resize' => [
                    900,
                    900,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                ]
            ]),
            $file->getFilesystem()
        );
    }

    private function generate($data, array $params = []): Image
    {
        $image = ImageManagerStatic::make($data);
        foreach ($params as $method => $param) {
            $image->$method(...$param);
        }

        return $image;
    }

    /**
     * @throws FilesystemException
     */
    private function save(string $path, Image $image, FilesystemOperator $filesystem): void
    {
        $filesystem->write($path, $image->encode()->getEncoded());
    }
}
