<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Functions\TwigExtension\ConvertSize;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Crud\Product\Files\Manage;
use EnjoysCMS\Module\Catalog\Crud\Product\Files\Upload;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Files extends AdminController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: "admin/catalog/product/files",
        name: "@a/catalog/product/files",
        options: [
            "comment" => "[ADMIN] Менеджер файлов"
        ]
    )]
    public function manage(Manage $manage): ResponseInterface
    {
        $this->twig->addExtension($this->container->get(ConvertSize::class));

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/files/manage.twig',
                $manage->getContext()
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: "admin/catalog/product/files/upload",
        name: "@a/catalog/product/files/upload",
        options: [
            "comment" => "[ADMIN] Загрузить файл для продукта"
        ]
    )]
    public function upload(Upload $upload): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $upload->getContext()
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
        path: "admin/catalog/product/files/delete",
        name: "@a/catalog/product/files/delete",
        options: [
            "comment" => "[ADMIN] Удалить загруженный файл"
        ]
    )]
    public function delete(
        RedirectInterface $redirect,
        ServerRequestInterface $request,
        EntityManager $em
    ): ResponseInterface {
        $product = $em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class)->find(
            $request->getQueryParams()['product'] ?? 0
        );
        /** @var ProductFiles $file */
        $file = $em->getRepository(ProductFiles::class)->findOneBy([
            'id' => $request->getQueryParams()['id'] ?? 0,
            'product' => $product,
        ]) ?? throw new NoResultException();

        $em->remove($file);
        $em->flush();

        $filesystem = $this->config->getFileStorageUpload($file->getStorage())->getFileSystem();
        $filesystem->delete($file->getFilePath());

        return $redirect->toRoute('@a/catalog/product/files', [
            'id' => $product->getId()
        ]);
    }
}
