<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Product\Quantity;

use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Quantity extends AdminController
{

    /**
     * @throws SyntaxError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     */
    #[Route(
        path: '/admin/catalog/product/quantity',
        name: '@catalog_product_quantity',
        comment: 'Установка количества на товар'
    )]
    public function manage(Manage $manage): ResponseInterface
    {
        $form = $manage->getForm();

        if ($form->isSubmitted()) {
            $manage->doAction();

            return$this->redirect->toUrl();
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);
        $this->breadcrumbs->add('@catalog_products', 'Список продуктов')->setLastBreadcrumb(
            sprintf('Настройка количества: `%s`', $manage->getProduct()->getName())
        );


        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/quantity/manage.twig',
                [
                    'product' => $manage->getProduct(),
                    'form' => $rendererForm->output(),
                    'subtitle' => 'Установка количества',
                ]
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
