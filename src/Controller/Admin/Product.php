<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Add;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Delete;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Edit;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Index;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Tags\TagsList;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Product extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    /**
     * @Route(
     *     path="admin/catalog/products",
     *     name="catalog/admin/products",
     *     options={
     *      "aclComment": "Просмотр товаров в админке"
     *     }
     * )
     * @return string
     */
    public function index(): string
    {
        return $this->view(
            $this->templatePath . '/products.twig',
            $this->getContext($this->container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/add",
     *     name="catalog/admin/product/add",
     *     options={
     *      "aclComment": "Добавление товара"
     *     }
     * )
     * @return string
     */
    public function add(): string
    {
        return $this->view(
            $this->templatePath . '/addproduct.twig',
            $this->getContext($this->container->get(Add::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/edit",
     *     name="catalog/admin/product/edit",
     *     options={
     *      "aclComment": "Редактирование товара"
     *     }
     * )
     * @return string
     */
    public function edit(): string
    {
        return $this->view(
            $this->templatePath . '/editproduct.twig',
            $this->getContext($this->container->get(Edit::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/delete",
     *     name="catalog/admin/product/delete",
     *     options={
     *      "aclComment": "Удаление товара"
     *     }
     * )
     * @return string
     */
    public function delete(): string
    {
        return $this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Delete::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/tags",
     *     name="@a/catalog/product/tags",
     *     options={
     *      "aclComment": "Просмотр тегов товара"
     *     }
     * )
     * @return string
     */
    public function manageTags(): string
    {
        return $this->view(
            $this->templatePath . '/product/tags/tags_list.twig',
            $this->getContext($this->container->get(TagsList::class))
        );
    }

}