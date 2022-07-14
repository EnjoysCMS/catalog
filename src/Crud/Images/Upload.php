<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use Enjoys\Upload\File;
use Enjoys\Upload\Storage\FileSystem;
use InvalidArgumentException;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;

    public function __construct()
    {
        if (!isset($_ENV['UPLOAD_DIR'])) {
            throw new InvalidArgumentException('Not set UPLOAD_DIR in .env');
        }
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

    public function getForm(): Form
    {
        $form = new Form();

        $form->file('image', 'Изображение')
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
    public function upload(ServerRequestWrapper $requestWrapper): void
    {
        $filename = md5((string)microtime(true));
        $subDirectory = $filename[0] . '/x/' . $filename[1];

        $storage = new FileSystem($_ENV['UPLOAD_DIR'] . '/catalog/' . $subDirectory);
        $file = new File($requestWrapper->getFilesData('image'), $storage);

        $file->setFilename($filename);
        try {
            $path = $file->upload();
            $this->setName($subDirectory . '/' . $file->getFilenameWithoutExtension());
            $this->setExtension($file->getExtension());
            $this->setFullPathFileNameWithExtension($path);
        } catch (\Throwable $e) {
            throw $e;
        }
    }


}
