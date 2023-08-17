<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Files;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Functions\TwigExtension\ConvertSize;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use EnjoysCMS\Module\Catalog\Events\PostUploadFile;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/catalog/product/files', '@catalog_product_files_')]
final class ProductFilesController extends AdminController
{
    private Product $product;

    public function __construct(Container $container, Config $config, \EnjoysCMS\Module\Admin\Config $adminConfig)
    {
        parent::__construct($container, $config, $adminConfig);

        $this->product = $container->get(EntityManager::class)->getRepository(Product::class)->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();

        $this->breadcrumbs->add('catalog/admin/products', 'Список продуктов')
            ->add(['@catalog_product_files_list', ['id' => $this->product->getId()]], 'Менеджер файлов');
    }

    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        name: "list",
        comment: 'Менеджер файлов (продукт)'
    )]
    public function manage(): ResponseInterface
    {
        $this->twig->addExtension($this->container->get(ConvertSize::class));

        $this->breadcrumbs->remove(['@catalog_product_files_list', ['id' => $this->product->getId()]])
            ->setLastBreadcrumb(
                sprintf('Менеджер файлов: %s', $this->product->getName())
            );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/files/manage.twig',
                [
                    'product' => $this->product,
                    'config' => $this->config,
                    'subtitle' => 'Управление файлами'
                ]
            )
        );
    }

    /**
     * @throws ExceptionRule
     * @throws FilesystemException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Route(
        path: "/upload",
        name: "upload",
        comment: 'Загрузка файла (продукт)'
    )]
    public function upload(Upload $upload, EntityManager $em): ResponseInterface
    {
        $form = $upload->getForm();

        if ($form->isSubmitted()) {
            $file = $upload->doAction($em, $this->request, $this->product, $this->config);
            $this->dispatcher->dispatch(new PostUploadFile($file));
            return $this->redirect->toRoute(
                '@catalog_product_files_list',
                ['id' => $this->product->getId()]
            );
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs->setLastBreadcrumb(sprintf('Загрузка файла для %s', $this->product->getName()));

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm,
                    'subtitle' => 'Загрузка файла',
                ]
            )
        );
    }

    /**
     * @throws FilesystemException
     * @throws NoResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotSupported
     */
    #[Route(
        path: "/delete",
        name: "delete",
        comment: 'Удалить загруженный файл (продукт)'
    )]
    public function delete(
        EntityManager $em
    ): ResponseInterface {
        $product = $em->getRepository(Product::class)->find(
            $this->request->getQueryParams()['product'] ?? 0
        ) ?? throw new NoResultException();

        /** @var ProductFiles $file */
        $file = $em->getRepository(ProductFiles::class)->findOneBy([
            'id' => $this->request->getQueryParams()['id'] ?? 0,
            'product' => $product,
        ]) ?? throw new NoResultException();

        $em->remove($file);
        $em->flush();

        $filesystem = $this->config->getFileStorageUpload($file->getStorage())->getFileSystem();
        $filesystem->delete($file->getFilePath());

        return $this->redirect->toRoute('@catalog_product_files_list', [
            'id' => $product->getId()
        ]);
    }
}
