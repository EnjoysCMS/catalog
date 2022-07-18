<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Functions\TwigExtension\ConvertSize;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Crud\Product\Files\Manage;
use EnjoysCMS\Module\Catalog\Crud\Product\Files\Upload;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
            "aclComment" => "[ADMIN] Менеджер файлов"
        ]
    )]
    public function manage(): ResponseInterface
    {
        $this->getTwig()->addExtension($this->container->get(ConvertSize::class));

        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/files/manage.twig',
                $this->getContext($this->container->get(Manage::class))
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
            "aclComment" => "[ADMIN] Загрузить файл для продукта"
        ]
    )]
    public function upload()
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/form.twig',
                $this->getContext($this->container->get(Upload::class))
            )
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws OptimisticLockException
     * @throws NoResultException
     */
    #[Route(
        path: "admin/catalog/product/files/delete",
        name: "@a/catalog/product/files/delete",
        options: [
            "aclComment" => "[ADMIN] Удалить загруженный файл"
        ]
    )]
    public function delete()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get(EntityManager::class);
        /** @var ServerRequestWrapper $request */
        $request = $this->getContainer()->get(ServerRequestWrapper::class);
        $product = $em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class)->find(
            $request->getQueryData('product', 0)
        );
        /** @var ProductFiles $file */
        $file = $em->getRepository(ProductFiles::class)->findOneBy([
            'id' => $request->getQueryData('id', 0),
            'product' => $product,
        ]);

        if ($file === null) {
            throw new NoResultException();
        }

        $em->remove($file);
        $em->flush();

        $filesystem = $this->config->getFileStorageUpload($file->getStorage())->getFileSystem();
        $filesystem->delete($file->getFilePath());

        Redirect::http(
            $this->getContainer()->get(UrlGeneratorInterface::class)->generate('@a/catalog/product/files', [
                'id' => $product->getId()
            ])
        );
    }
}
