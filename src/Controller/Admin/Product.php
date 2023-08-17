<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Add;
use EnjoysCMS\Module\Catalog\Admin\Product\Delete;
use EnjoysCMS\Module\Catalog\Admin\Product\Edit;
use EnjoysCMS\Module\Catalog\Admin\Product\Tags\TagsList;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Product extends AdminController
{

    #[Route(
        path: 'admin/catalog/products',
        name: 'catalog/admin/products',
        options: [
            'comment' => 'Просмотр товаров в админке'
        ]
    )]
    public function index(): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Список товаров (продуктов)');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/products.twig'
            )
        );
    }


    #[Route(
        path: 'admin/catalog/product/add',
        name: 'catalog/admin/product/add',
        options: [
            'comment' => 'Добавление товара'
        ]
    )]
    public function add(Add $add): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/addproduct.twig',
                $add->getContext()
            )
        );
    }


    #[Route(
        path: 'admin/catalog/product/edit',
        name: 'catalog/admin/product/edit',
        options: [
            'comment' => 'Редактирование товара'
        ]
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editproduct.twig',
                $edit->getContext()
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/delete',
        name: 'catalog/admin/product/delete',
        options: [
            'comment' => 'Удаление товара'
        ]
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $delete->getContext()
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/tags',
        name: '@a/catalog/product/tags',
        options: [
            'comment' => 'Просмотр тегов товара'
        ]
    )]
    public function manageTags(TagsList $tagsList): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/tags/tags_list.twig',
                $tagsList->getContext()
            )
        );
    }


    #[Route(
        path: 'admin/catalog/tools/find-products',
        name: '@a/catalog/tools/find-products',
        options: [
            'comment' => '[JSON] Получение списка продукции (поиск)'
        ]
    )]
    public function findProductsByLike(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        $matched = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class)->like(
            $request->getQueryParams()['query'] ?? null
        );

        $result = [
            'items' => array_map(function ($item) {
                /** @var \EnjoysCMS\Module\Catalog\Entities\Product $item */
                return [
                    'id' => $item->getId(),
                    'title' => $item->getName(),
                    'category' => $item->getCategory()->getFullTitle()
                ];
            }, $matched),
            'total_count' => count($matched)
        ];
        return $this->jsonResponse($result);
    }


}
