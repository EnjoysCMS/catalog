<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\Currency\Add;
use EnjoysCMS\Module\Catalog\Crud\Currency\Delete;
use EnjoysCMS\Module\Catalog\Crud\Currency\Edit;
use EnjoysCMS\Module\Catalog\Crud\Currency\Manage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Currency extends AdminController
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/currency',
        name: 'catalog/admin/currency',
        options: [
            'comment' => '[ADMIN] Просмотр списка валют в админке'
        ]
    )]
    public function manage(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/currency.twig',
            $this->getContext($this->container->get(Manage::class))
        ));
    }

    #[Route(
        path: 'admin/catalog/currency/add',
        name: 'catalog/admin/currency/add',
        options: [
            'comment' => '[ADMIN] Добавление валюты'
        ]
    )]
    public function add(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Add::class))
        ));
    }

    #[Route(
        path: 'admin/catalog/currency/edit',
        name: 'catalog/admin/currency/edit',
        options: [
            'comment' => '[ADMIN] Редактирование валюты'
        ]
    )]
    public function edit(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Edit::class))
        ));
    }


    #[Route(
        path: 'admin/catalog/currency/delete',
        name: 'catalog/admin/currency/delete',
        options: [
            'comment' => '[ADMIN] Удаление валюты'
        ]
    )]
    public function delete(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Delete::class))
        ));
    }
}
