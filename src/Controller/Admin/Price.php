<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;



use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Crud\Product\Price\Manage;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Price extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    /**
     * @Route(
     *     path="admin/catalog/product/prices",
     *     name="@a/catalog/product/prices",
     *     options={
     *      "aclComment": "[ADMIN] Установка цен товару"
     *     }
     * )
     */
    public function manage(): string
    {
        return $this->view(
            $this->templatePath . '/product/prices/manage.twig',
            $this->getContext($this->container->get(Manage::class))
        );
    }
}
