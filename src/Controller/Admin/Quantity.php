<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use EnjoysCMS\Module\Catalog\Crud\Product\Quantity\Manage;
use EnjoysCMS\Module\Catalog\Crud\Product\Quantity\Params;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Quantity extends AdminController
{

    #[Route(
        path: "admin/catalog/product/quantity",
        name: "@a/catalog/product/quantity",
        options: [
            "comment" => "[ADMIN] Установка количества на товар"
        ]
    )]
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/quantity/manage.twig',
                $this->getContext($this->container->get(Manage::class))
            )
        );
    }
//
//    #[Route(
//        path: "admin/catalog/product/quantity-params",
//        name: "@a/catalog/product/quantity-params",
//        options: [
//            "comment" => "[ADMIN] Установка параметров для количества"
//        ]
//    )]
//    public function setParams(): ResponseInterface
//    {
//        return $this->responseText(
//            $this->view(
//                $this->templatePath . '/product/quantity/params.twig',
//                $this->getContext($this->container->get(Params::class))
//            )
//        );
//    }
}
