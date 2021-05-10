<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;


use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\UploadFileSystem;
use Exception;
use InvalidArgumentException;
use Upload\File;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;

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
        $form = new Form(['method' => 'post']);

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
            ->setAttribute('accept', '.png, .jpg, .jpeg')
        ;

        $form->submit('upload');
        return $form;
    }



    public function upload(ServerRequestInterface $serverRequest): void
    {
        $storage = new UploadFileSystem($_ENV['UPLOAD_DIR']);
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