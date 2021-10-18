<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls\AddUrl;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls\DeleteUrl;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls\EditUrl;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls\MakeDefault;
use EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls\Manage;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Urls extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    /**
     * @Route(
     *     path="admin/catalog/product/urls",
     *     name="@a/catalog/product/urls",
     *     options={
     *      "aclComment": "[ADMIN] Просмотр URLs товара"
     *     }
     * )
     * @return string
     */
    public function manage(): string
    {
        return $this->view(
            $this->templatePath . '/product/urls/manage.twig',
            $this->getContext($this->container->get(Manage::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/urls/edit",
     *     name="@a/catalog/product/urls/edit",
     *     options={
     *      "aclComment": "[ADMIN] Редактирование URL"
     *     }
     * )
     */
    public function edit()
    {
        return $this->view(
            $this->templatePath . '/product/urls/edit.twig',
            $this->getContext($this->container->get(EditUrl::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/urls/add",
     *     name="@a/catalog/product/urls/add",
     *     options={
     *      "aclComment": "[ADMIN] Добавление URL"
     *     }
     * )
     */
    public function add()
    {
        return $this->view(
            $this->templatePath . '/product/urls/add.twig',
            $this->getContext($this->container->get(AddUrl::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/urls/delete",
     *     name="@a/catalog/product/urls/delete",
     *     options={
     *      "aclComment": "[ADMIN] Удаление URL"
     *     }
     * )
     */
    public function delete()
    {
        return $this->view(
            $this->templatePath . '/product/urls/delete.twig',
            $this->getContext($this->container->get(DeleteUrl::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/urls/makedefault",
     *     name="@a/catalog/product/urls/makedefault",
     *     options={
     *      "aclComment": "[ADMIN] Сделать URL основным"
     *     }
     * )
     */
    public function makeDefault()
    {
        $this->container->get(MakeDefault::class)();
    }
}