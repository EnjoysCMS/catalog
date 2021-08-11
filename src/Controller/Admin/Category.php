<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Add;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Delete;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Edit;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Index;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Category extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }


    /**
     * @Route(
     *     path="catalog/admin/category",
     *     name="catalog/admin/category",
     *     options={
     *      "aclComment": "Просмотр списка категорий в админке"
     *     }
     * )
     * @return string
     */
    public function index(): string
    {
        return $this->view(
            $this->templatePath . '/category.twig',
            $this->getContext($this->container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/category/add",
     *     name="catalog/admin/category/add",
     *     options={
     *      "aclComment": "Добавление категорий"
     *     }
     * )
     * @return string
     */
    public function add(): string
    {
        return $this->view(
            $this->templatePath . '/addcategory.twig',
            $this->getContext($this->container->get(Add::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/category/edit",
     *     name="catalog/admin/category/edit",
     *     options={
     *      "aclComment": "Редактирование категорий"
     *     }
     * )
     * @return string
     */
    public function edit(): string
    {
        return $this->view(
            $this->templatePath . '/editcategory.twig',
            $this->getContext($this->container->get(Edit::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/category/delete",
     *     name="catalog/admin/category/delete",
     *     options={
     *      "aclComment": "Удаление категорий"
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

}