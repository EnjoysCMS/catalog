<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupAdd;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupDelete;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupEdit;
use EnjoysCMS\Module\Catalog\Crud\PriceGroup\PriceGroupList;
use EnjoysCMS\Module\Catalog\Crud\PriceGroupModel;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;


final class PriceGroup extends AdminController
{


    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup',
        name: 'catalog/admin/pricegroup'
    )]
    public function list(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/PriceGroup/price_group_list.twig',
            $this->getContext($this->getContainer()->get(PriceGroupList::class))
        ));
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/add',
        name: 'catalog/admin/pricegroup/add'
    )]
    public function add(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/PriceGroup/price_group_add.twig',
            $this->getContext($this->getContainer()->get(PriceGroupAdd::class))
        ));
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/edit',
        name: 'catalog/admin/pricegroup/edit'
    )]
    public function edit(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/PriceGroup/price_group_edit.twig',
            $this->getContext($this->getContainer()->get(PriceGroupEdit::class))
        ));
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/pricegroup/delete',
        name: 'catalog/admin/pricegroup/delete'
    )]
    public function delete(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/PriceGroup/price_group_delete.twig',
            $this->getContext($this->container->get(PriceGroupDelete::class))
        ));
    }
}
