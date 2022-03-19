<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Crud\Images\Add;
use EnjoysCMS\Module\Catalog\Crud\Images\Delete;
use EnjoysCMS\Module\Catalog\Crud\Images\Index;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
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
        return $this->responseText($this->view(
            $this->templatePath . '/product/images/manage.twig',
            $this->getContext($this->container->get(Index::class))
        ));
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
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Add::class))
        ));
    }

    /**
     * @Route(
     *     path="admin/catalog/product/images/make_general",
     *     name="catalog/admin/product/images/make_general",
     *     options={
     *      "aclComment": "Переключение основного изображения"
     *     }
     * )
     */
    public function makeGeneral(EntityManager $entityManager, ServerRequestInterface $serverRequest, UrlGeneratorInterface $urlGenerator): void
    {
        $repository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Image::class);
        $image = $repository->find($serverRequest->get('id'));
        if ($image === null) {
            Error::code(404);
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
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Delete::class))
        ));
    }
}
