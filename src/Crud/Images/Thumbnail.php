<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Upload\File;
use Intervention\Image\ImageManagerStatic;

final class Thumbnail
{
    public static function generateAndSave(string $path, File $file, array $params = [])
    {
        $img = ImageManagerStatic::make($file->getUploadedFile()->getStream()->getContents());
        foreach ($params as $method => $param) {
            $img->$method(...$param);
        }
        $file->getFilesystem()->write($path, $img->encode()->getEncoded());

    }
}
