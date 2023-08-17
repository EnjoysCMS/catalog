<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Crud\Images\Add;
use EnjoysCMS\Module\Catalog\Crud\Images\Delete;
use EnjoysCMS\Module\Catalog\Crud\Images\Index;
use EnjoysCMS\Module\Catalog\Crud\Images\ManageImage;
use EnjoysCMS\Module\Catalog\Crud\Images\UploadHandler;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

final class Image extends AdminController
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/images',
        name: 'catalog/admin/product/images',
        options: [
            'comment' => 'Управление изображениями товара'
        ]
    )]
    public function manage(Index $index): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/images/manage.twig',
                $index->getContext()
            )
        );
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/images/add',
        name: 'catalog/admin/product/images/add',
        options: [
            'comment' => 'Загрузка изображения к товару'
        ]
    )]
    public function add(Add $addImageService): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $addImageService->getTemplatePath($this->templatePath),
                $addImageService->getContext()
            )
        );
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotFoundException
     */
    #[Route(
        path: 'admin/catalog/product/images/make_general',
        name: 'catalog/admin/product/images/make_general',
        options: [
            'comment' => 'Переключение основного изображения'
        ]
    )]
    public function makeGeneral(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        RedirectInterface $redirect
    ): ResponseInterface {
        $repository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Image::class);
        $image = $repository->find($request->getQueryParams()['id'] ?? null) ?? throw new NotFoundException(
            sprintf('Not found by id: %s', $request->getQueryParams()['id'] ?? null)
        );

        $images = $repository->findBy(['product' => $image->getProduct()]);
        foreach ($images as $item) {
            $item->setGeneral(false);
        }
        $image->setGeneral(true);
        $entityManager->flush();

        return $redirect->toRoute(
            'catalog/admin/product/images',
            ['product_id' => $image->getProduct()->getId()]
        );
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/images/delete',
        name: 'catalog/admin/product/images/delete',
        options: [
            'comment' => 'Удаление изображения к товару'
        ]
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $delete->getContext()
            )
        );
    }

    #[Route(
        path: '/admin/catalog/product/images/upload-dropzone',
        name: 'catalog/admin/product/images/upload-dropzone',
        options: [
            'comment' => '[Admin][Simple Gallery] Загрузка изображений с помощью dropzone.js'
        ]
    )]
    public function uploadDropzone(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        UploadHandler $uploadHandler
    ): ResponseInterface {
        try {
            $product = $entityManager->getRepository(Product::class)->find(
                $request->getQueryParams()['product_id'] ?? 0
            );
            if ($product === null) {
                throw new NotFoundException(
                    sprintf('Not found by product_id: %s', $request->getQueryParams()['product_id'] ?? 0)
                );
            }

            $file = $uploadHandler->uploadFile($request->getUploadedFiles()['image']);
            $manageImage = new ManageImage($product, $entityManager, $this->config);
            $manageImage->addToDB(
                str_replace($file->getFileInfo()->getExtensionWithDot(), '', $file->getTargetPath()),
                $file->getFileInfo()->getExtension()
            );
        } catch (Throwable $e) {
            $this->response = $this->response->withStatus(500);
            $errorMessage = htmlspecialchars(sprintf('%s: %s', $e::class, $e->getMessage()));
        }
        return $this->jsonResponse($errorMessage ?? 'uploaded');
    }
}
