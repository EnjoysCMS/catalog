<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Files;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use Enjoys\Upload\File;
use Enjoys\Upload\UploadProcessing;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Upload implements ModelInterface
{

    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private RendererInterface $renderer,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer,
            'subtitle' => 'Загрузка файла',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                $this->urlGenerator->generate('@a/catalog/product/files', ['id' => $this->product->getId()]
                ) => 'Менеджер файлов',
                sprintf('Загрузка файла для %s', $this->product->getName()),
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->text('title', 'Наименование')->setDescription('Не обязательно');
        $form->text('description', 'Описание')->setDescription('Не обязательно');
        $form->file('file', 'Выберите файл')->addRule(Rules::UPLOAD, ['required']);
        $form->submit(uniqid('submit'));
        return $form;
    }

    private function doAction()
    {

        $uploadedFile = $this->requestWrapper->getFilesData('file');
        $storage = $this->config->getFileStorageUpload();
        $filesystem = $storage->getFileSystem();

        $file = new UploadProcessing($uploadedFile, $filesystem);

        $newName = md5((string)microtime(true));
        $file->setFilename($newName[0] . '/' . $newName);
        try {
            $file->upload();

            $productFile = new ProductFiles();
            $productFile->setProduct($this->product);
            $productFile->setFilePath($file->getFileInfo()->getFilename());
            $productFile->setFileSize($file->getFileInfo()->getSize());
            $productFile->setFileExtension($file->getFileInfo()->getExtension());
            $productFile->setOriginalFilename($file->getFileInfo()->getOriginalFilename());
            $productFile->setDescription($this->requestWrapper->getPostData('description'));
            $productFile->setTitle($this->requestWrapper->getPostData('title'));
            $productFile->setStorage($this->config->getModuleConfig()->get('productFileStorage'));

            $this->em->persist($productFile);
            $this->em->flush();

            Redirect::http(
                $this->urlGenerator->generate('@a/catalog/product/files', [
                    'id' => $this->product->getId()
                ])
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
