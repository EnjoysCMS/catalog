<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Files;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Upload\UploadProcessing;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Upload implements ModelInterface
{

    private EntityRepository|ProductRepository $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private RendererInterface $renderer,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
        private Config $config
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws FilesystemException
     */
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

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->text('title', 'Наименование')->setDescription('Не обязательно');
        $form->text('description', 'Описание')->setDescription('Не обязательно');
        $form->file('file', 'Выберите файл')->addRule(Rules::UPLOAD, ['required']);
        $form->submit(uniqid('submit'));
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws FilesystemException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $uploadedFile = $this->request->getUploadedFiles()['file'] ?? throw new InvalidArgumentException(
            'File not choose or send'
        );
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
            $productFile->setDescription($this->request->getParsedBody()['description'] ?? null);
            $productFile->setTitle($this->request->getParsedBody()['title'] ?? null);
            $productFile->setStorage($this->config->get('productFileStorage'));

            $this->em->persist($productFile);
            $this->em->flush();

            $this->redirect->http(
                $this->urlGenerator->generate('@a/catalog/product/files', [
                    'id' => $this->product->getId()
                ]),
                emit: true
            );
        } catch (Exception $e) {
            throw $e;
        }
    }
}
