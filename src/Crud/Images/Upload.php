<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;

    public function __construct(private UploadHandler $uploadHandler)
    {
    }

    public function getTemplatePath(string $templateRootPath): string
    {
        return $templateRootPath .'/product/images/upload.twig';
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
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();

        $form->file('image', 'Изображение')
            ->addRule(
                Rules::UPLOAD,
                [
                    'required',
                ]
            )->setAttribute(AttributeFactory::create('accept', '.png, .jpg, .jpeg'));

        $form->submit('upload');
        return $form;
    }


    /**
     * @param ServerRequestInterface $request
     * @return \Generator
     * @throws FilesystemException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Throwable
     */
    public function upload(ServerRequestInterface $request): \Generator
    {
            try {
                $file = $this->uploadHandler->uploadFile($request->getUploadedFiles()['image'] ?? null);
                $this->setName( str_replace($file->getFileInfo()->getExtensionWithDot(), '', $file->getTargetPath()));
                $this->setExtension($file->getFileInfo()->getExtension());
                yield $this;
            } catch (\Throwable $e) {
                throw $e;
            }
    }



}
