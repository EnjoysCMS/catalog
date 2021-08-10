<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Add;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Delete;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Edit;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Index;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Product extends BaseController
{

    private string $templatePath;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    /**
     * @Route(
     *     path="catalog/admin/products",
     *     name="catalog/admin/products",
     *     options={
     *      "aclComment": "Просмотр товаров в админке"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function index(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/products.twig',
            $this->getContext($container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/product/add",
     *     name="catalog/admin/product/add",
     *     options={
     *      "aclComment": "Добавление товара"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function add(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/addproduct.twig',
            $this->getContext($container->get(Add::class))
        );
    }


    /**
     * @Route(
     *     path="catalog/admin/product/edit",
     *     name="catalog/admin/product/edit",
     *     options={
     *      "aclComment": "Редактирование товара"
     *     }
     * )
     * @param ContainerInterface $container
     * @return string
     */
    public function edit(ContainerInterface $container): string
    {
        return $this->view(
            $this->templatePath . '/editproduct.twig',
            $this->getContext($container->get(Edit::class))
        );
    }

    /**
     * @Route(
     *     path="catalog/admin/product/delete",
     *     name="catalog/admin/product/delete",
     *     options={
     *      "aclComment": "Удаление товара"
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