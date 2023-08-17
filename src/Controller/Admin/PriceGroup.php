<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupAdd;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupDelete;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupEdit;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupList;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;


final class PriceGroup extends AdminController
{


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup',
        name: 'catalog/admin/pricegroup'
    )]
    public function list(PriceGroupList $priceGroupList): ResponseInterface
    {
        return $this->response($this->twig->render(
            $this->templatePath . '/PriceGroup/price_group_list.twig',
            $priceGroupList->getContext()
        ));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/add',
        name: 'catalog/admin/pricegroup/add'
    )]
    public function add(PriceGroupAdd $priceGroupAdd): ResponseInterface
    {
        return $this->response($this->twig->render(
            $this->templatePath . '/PriceGroup/price_group_add.twig',
            $priceGroupAdd->getContext()
        ));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/edit',
        name: 'catalog/admin/pricegroup/edit'
    )]
    public function edit(PriceGroupEdit $priceGroupEdit): ResponseInterface
    {
        return $this->response($this->twig->render(
            $this->templatePath . '/PriceGroup/price_group_edit.twig',
            $priceGroupEdit->getContext()
        ));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/delete',
        name: 'catalog/admin/pricegroup/delete'
    )]
    public function delete(PriceGroupDelete $priceGroupDelete): ResponseInterface
    {
        return $this->response($this->twig->render(
            $this->templatePath . '/PriceGroup/price_group_delete.twig',
            $priceGroupDelete->getContext()
        ));
    }
}
