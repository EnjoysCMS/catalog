<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\Product\Urls\AddUrl;
use EnjoysCMS\Module\Catalog\Crud\Product\Urls\DeleteUrl;
use EnjoysCMS\Module\Catalog\Crud\Product\Urls\EditUrl;
use EnjoysCMS\Module\Catalog\Crud\Product\Urls\MakeDefault;
use EnjoysCMS\Module\Catalog\Crud\Product\Urls\Manage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Urls extends AdminController
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/urls',
        name: '@a/catalog/product/urls',
        options: [
            'comment' => '[ADMIN] Просмотр URLs товара'
        ]
    )]
    public function manage(Manage $manage): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/manage.twig',
                $manage->getContext()
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/urls/edit',
        name: '@a/catalog/product/urls/edit',
        options: [
            'comment' => '[ADMIN] Редактирование URL'
        ]
    )]
    public function edit(EditUrl $editUrl): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/edit.twig',
                $editUrl->getContext()
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/urls/add',
        name: '@a/catalog/product/urls/add',
        options: [
            'comment' => '[ADMIN] Добавление URL'
        ]
    )]
    public function add(AddUrl $addUrl): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/add.twig',
                $addUrl->getContext()
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/urls/delete',
        name: '@a/catalog/product/urls/delete',
        options: [
            'comment' => '[ADMIN] Удаление URL'
        ]
    )]
    public function delete(DeleteUrl $deleteUrl): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/delete.twig',
                $deleteUrl->getContext()
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/urls/makedefault',
        name: '@a/catalog/product/urls/makedefault',
        options: [
            'comment' => '[ADMIN] Сделать URL основным'
        ]
    )]
    public function makeDefault(): void
    {
        $this->container->get(MakeDefault::class)();
    }
}
