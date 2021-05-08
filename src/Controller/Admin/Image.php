<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Core\Components\Helpers\Assets;
use EnjoysCMS\Module\Catalog\Models\Admin\Images\Add;
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
}