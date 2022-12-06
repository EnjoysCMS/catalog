<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Crud\Images\Add;
use EnjoysCMS\Module\Catalog\Crud\Images\Delete;
use EnjoysCMS\Module\Catalog\Crud\Images\Index;
use EnjoysCMS\Module\Catalog\Crud\Images\ManageImage;
use EnjoysCMS\Module\Catalog\Crud\Images\UploadHandler;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Image extends AdminController
{

    /**
     * @Route(
     *     path="admin/catalog/product/images",
     *     name="catalog/admin/product/images",
     *     options={
     *      "aclComment": "Управление изображениями товара"
     *     }
     * )
     * @return string
     */
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/images/manage.twig',
                $this->getContext($this->container->get(Index::class))
            )
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/images/add",
     *     name="catalog/admin/product/images/add",
     *     options={
     *      "aclComment": "Загрузка изображения к товару"
     *     }
     * )
     * @return string
     */
    public function add(): ResponseInterface
    {
        /** @var Add $addImageService */
        $addImageService = $this->container->get(Add::class);
        return $this->responseText(
            $this->view(
                $addImageService->getTemplatePath($this->templatePath),
                $this->getContext($addImageService)
            )
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/images/make_general",
     *     name="catalog/admin/product/images/make_general",
     *     options={
     *      "aclComment": "Переключение основного изображения"
     *     }
     * )
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotFoundException
     */
    public function makeGeneral(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        UrlGeneratorInterface $urlGenerator
    ): void {
        $repository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Image::class);
        $image = $repository->find($request->getQueryParams()['id'] ?? null);
        if ($image === null) {
            throw new NotFoundException(
                sprintf('Not found by id: %s', $request->getQueryParams()['id'] ?? null)
            );
        }
        $images = $repository->findBy(['product' => $image->getProduct()]);
        foreach ($images as $item) {
            $item->setGeneral(false);
        }
        $image->setGeneral(true);
        $entityManager->flush();
        Redirect::http(
            $urlGenerator->generate(
                'catalog/admin/product/images',
                ['product_id' => $image->getProduct()->getId()]
            )
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/images/delete",
     *     name="catalog/admin/product/images/delete",
     *     options={
     *      "aclComment": "Удаление изображения к товару"
     *     }
     * )
     * @return string
     */
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/form.twig',
                $this->getContext($this->container->get(Delete::class))
            )
        );
    }

    #[Route(
        path: '/admin/catalog/product/images/upload-dropzone',
        name: 'catalog/admin/product/images/upload-dropzone',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений с помощью dropzone.js'
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
        } catch (\Throwable $e) {
            $this->response = $this->response->withStatus(500);
            $errorMessage = htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage()));
        }
        return $this->responseJson($errorMessage ?? 'uploaded');
    }
}
