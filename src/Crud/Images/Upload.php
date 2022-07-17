<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use Enjoys\Upload\File;
use Enjoys\Upload\Storage\FileSystem;
use EnjoysCMS\Module\Catalog\FileSystemAdapterInterface;
use EnjoysCMS\Module\Catalog\StorageUpload\StorageUploadInterface;
use Psr\Http\Message\UploadedFileInterface;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;
    private StorageUploadInterface $storageUpload;

    public function __construct($config)
    {
        //$configFilesystem = $config['image'] ?? throw new \RuntimeException('Not set config `manageUploads.image`');
        /** @var class-string $fileSystemClass */
        $fileSystemClass = key($config);
        $this->storageUpload = new $fileSystemClass(...current($config));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    private function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    private function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return string
     */
//    public function getFullPathFileNameWithExtension(): string
//    {
//        return $this->fullPathFileNameWithExtension;
//    }

//    /**
//     * @param string $fullPathFileNameWithExtension
//     */
//    private function setFullPathFileNameWithExtension(string $fullPathFileNameWithExtension): void
//    {
//        $this->fullPathFileNameWithExtension = $fullPathFileNameWithExtension;
//    }

    public function getForm(): Form
    {
        $form = new Form();

        $form->file('image', 'Изображение')
            ->setMultiple()
            ->addRule(
                Rules::UPLOAD,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 2,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )->setAttribute(AttributeFactory::create('accept', '.png, .jpg, .jpeg'));

        $form->submit('upload');
        return $form;
    }


    /**
     * @throws \Exception
     */
    public function upload(ServerRequestWrapper $requestWrapper): \Generator
    {
        $uploadedFiles = $this->getUploadedFiles($requestWrapper);
        foreach ($uploadedFiles as $uploadedFile) {
            $newFilename = md5((string)microtime(true));
            $subDirectory = 'test/' . $newFilename[0] . '/' . $newFilename[1] . '/';


            $file = new File($uploadedFile, $this->storageUpload->getFileSystem());

            $file->setFilename($newFilename);
            try {
                $file->upload($subDirectory);
                Thumbnail::generateAndSave(
                    $subDirectory . $file->getFilenameWithoutExtension() . '_small' . $file->getExtensionWithDot(),
                    $file,
                    [
                        'resize' => [
                            300,
                            300,
                            function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            }
                        ]
                    ]
                );
                Thumbnail::generateAndSave(
                    $subDirectory . $file->getFilenameWithoutExtension() . '_large' . $file->getExtensionWithDot(),
                    $file,
                    [
                        'resize' => [
                            900,
                            900,
                            function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            }
                        ]
                    ]
                );

                $this->setName($subDirectory . $file->getFilenameWithoutExtension());
                $this->setExtension($file->getExtension());

                yield $this;
            } catch (\Throwable $e) {
                throw $e;
            }
        }
    }

    /**
     * @param ServerRequestWrapper $request
     * @return UploadedFileInterface[]
     */
    private function getUploadedFiles(ServerRequestWrapper $request): array
    {
        $images = $request->getFilesData('image');
        if (is_array($images)) {
            return $images;
        }
        return [$images];
    }


}
