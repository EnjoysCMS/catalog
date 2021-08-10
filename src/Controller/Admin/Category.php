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

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer,
        ContainerInterface $container,
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
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
     * @param ContainerInterface $container
     * @return string
     */
    public function index(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/category.twig',
            $this->getContext($container->get(Index::class))
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
     * @param ContainerInterface $container
     * @return string
     */
    public function add(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/addcategory.twig',
            $this->getContext($container->get(Add::class))
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
     * @param ContainerInterface $container
     * @return string
     */
    public function edit(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/editcategory.twig',
            $this->getContext($container->get(Edit::class))
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
     * @param ContainerInterface $container
     * @return string
     */
    public function delete(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($container->get(Delete::class))
        );
    }

}