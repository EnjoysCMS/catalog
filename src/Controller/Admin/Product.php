<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Crud\Product\Add;
use EnjoysCMS\Module\Catalog\Crud\Product\Delete;
use EnjoysCMS\Module\Catalog\Crud\Product\Edit;
use EnjoysCMS\Module\Catalog\Crud\Product\Index;
use EnjoysCMS\Module\Catalog\Crud\Product\Tags\TagsList;
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
        return $this->responseText(
            $this->view(
                $this->templatePath . '/products.twig',
                $this->getContext($this->container->get(Index::class))
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
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/addproduct.twig',
                $this->getContext($this->container->get(Add::class))
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
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/editproduct.twig',
                $this->getContext($this->container->get(Edit::class))
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
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/form.twig',
                $this->getContext($this->container->get(Delete::class))
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
    public function manageTags(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/tags/tags_list.twig',
                $this->getContext($this->container->get(TagsList::class))
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
        return $this->responseJson($result);
    }


}
