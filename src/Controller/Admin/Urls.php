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
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/urls/manage.twig',
                $this->getContext($this->container->get(Manage::class))
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
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/urls/edit.twig',
                $this->getContext($this->container->get(EditUrl::class))
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
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/urls/add.twig',
                $this->getContext($this->container->get(AddUrl::class))
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
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/urls/delete.twig',
                $this->getContext($this->container->get(DeleteUrl::class))
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
