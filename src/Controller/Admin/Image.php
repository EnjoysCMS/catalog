<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Models\Admin\Images\Add;
use EnjoysCMS\Module\Catalog\Models\Admin\Images\Delete;
use EnjoysCMS\Module\Catalog\Models\Admin\Images\Index;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Image extends BaseController
{
    private string $templatePath = __DIR__ . '/../../../template';

    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return realpath($this->templatePath);
    }

    /**
     * @Route(
     *     path="catalog/admin/product/images",
     *     name="catalog/admin/product/images",
     *     options={
     *      "aclComment": "Управление изображениями товара"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function manage(ContainerInterface $container): string
    {

        return $this->view(
            $this->getTemplatePath() . '/admin/images.twig',
            $this->getContext($container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/product/images/add",
     *     name="catalog/admin/product/images/add",
     *     options={
     *      "aclComment": "Загрузка изображения к товару"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function add(ContainerInterface $container): string
    {

        return $this->view(
            $this->getTemplatePath() . '/admin/form.twig',
            $this->getContext($container->get(Add::class))
        );
    }

    /**
     * @Route(
     *     path="catalog/admin/product/images/make_general",
     *     name="catalog/admin/product/images/make_general",
     *     options={
     *      "aclComment": "Переключение основного изображения"
     *     }
     * )
     */
    public function makeGeneral(): void
    {
        $repository = $this->entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Image::class);
        $image = $repository->find($this->serverRequest->get('id'));
        if($image === null){
            Error::code(404);
        }
        $images = $repository->findBy(['product' => $image->getProduct()]);
        foreach ($images as $item) {
            $item->setGeneral(false);
        }
        $image->setGeneral(true);
        $this->entityManager->flush();
        Redirect::http(
            $this->urlGenerator->generate(
                'catalog/admin/product/images',
                ['product_id' => $image->getProduct()->getId()]
            )
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/product/images/delete",
     *     name="catalog/admin/product/images/delete",
     *     options={
     *      "aclComment": "Удаление изображения к товару"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function delete(ContainerInterface $container): string
    {

        return $this->view(
            $this->getTemplatePath() . '/admin/form.twig',
            $this->getContext($container->get(Delete::class))
        );
    }
}
