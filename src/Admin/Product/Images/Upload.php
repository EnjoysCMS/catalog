<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Images;


use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Generator;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class Upload implements LoadImage
{
    private string $name;
    private string $extension;

    public function __construct(private readonly UploadHandler $uploadHandler)
    {
    }

    public function getTemplatePath(string $templateRootPath): string
    {
        return $templateRootPath .'/product/images/upload.twig';
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

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
     * @throws FilesystemException
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Throwable
     */
    public function upload(ServerRequestInterface $request): Generator
    {
            try {
                $file = $this->uploadHandler->uploadFile($request->getUploadedFiles()['image'] ?? null);
                $this->setName( str_replace($file->getFileInfo()->getExtensionWithDot(), '', $file->getTargetPath()));
                $this->setExtension($file->getFileInfo()->getExtension());
                yield $this;
            } catch (Throwable $e) {
                throw $e;
            }
    }



}
