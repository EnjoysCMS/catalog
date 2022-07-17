<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use Enjoys\Upload\File;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Http\Message\UploadedFileInterface;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;
    private ThumbnailService\ThumbnailServiceInterface $thumbnailService;
    private \League\Flysystem\FilesystemOperator $filesystem;

    public function __construct(private Config $config)
    {
        $this->filesystem = $this->config->getImageStorageUpload()->getFileSystem();
        $this->thumbnailService = $this->config->getThumbnailService();
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
            $subDirectory = $newFilename[0] . '/' . $newFilename[1];


            $file = new File($uploadedFile, $this->filesystem);

            $file->setFilename($newFilename);
            try {
                $file->upload($subDirectory);
                $this->thumbnailService->make(
                    $this->filesystem,
                    $file->getTargetPath(),
                    $file->getUploadedFile()->getStream()->getContents()
                );

                $this->setName($subDirectory . '/' . $file->getFilenameWithoutExtension());
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
