<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\Product\Price\Manage;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Price extends AdminController
{

    #[Route(
        path: 'admin/catalog/product/prices',
        name: '@a/catalog/product/prices',
        options: [
            'comment' => '[ADMIN] Установка цен товару'
        ]
    )]
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/prices/manage.twig',
                $this->getContext($this->container->get(Manage::class))
            )
        );
    }
}
