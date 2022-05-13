<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Module\Catalog\UploadFileSystem;
use Exception;
use InvalidArgumentException;
use Upload\File;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;

    public function __construct()
    {
        if(!isset($_ENV['UPLOAD_DIR'])){
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
                null,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 2,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )
            ->setAttribute(AttributeFactory::create('accept', '.png, .jpg, .jpeg'));

        $form->submit('upload');
        return $form;
    }


    public function upload(ServerRequestWrapper $requestWrapper): void
    {
        $storage = new UploadFileSystem($_ENV['UPLOAD_DIR'] . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR);
        $file = new File('image', $storage);
        $newName = md5((string)microtime(true));
        $file->setName($newName[0] . '/' . $newName[1] . '/' . $newName);
        try {
            $file->upload();
            $this->setName($file->getName());
            $this->setExtension($file->getExtension());
            $this->setFullPathFileNameWithExtension($storage->getFullPathFileNameWithExtension());
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage() . ' ' . implode(", ", $file->getErrors()));
        }
    }


}
