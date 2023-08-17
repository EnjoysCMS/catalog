<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Images\ThumbnailService;


use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

final class DefaultThumbnailService implements ThumbnailServiceInterface
{


    /**
     * @throws FilesystemException
     */
    public function make(FilesystemOperator $filesystem, string $filename, $data): void
    {

        $this->save(
            str_replace(
                '.',
                '_small.',
                $filename
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
            $filesystem
        );

        $this->save(
            str_replace(
                '.',
                '_large.',
                $filename
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
            $filesystem
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
