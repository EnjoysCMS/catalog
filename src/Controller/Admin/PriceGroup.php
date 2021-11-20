<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup\PriceGroupAdd;
use EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup\PriceGroupEdit;
use EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup\PriceGroupList;
use EnjoysCMS\Module\Catalog\Models\Admin\PriceGroupModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;


final class PriceGroup extends BaseController
{
    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    #[Route(
        path: 'admin/catalog/pricegroup',
        name: 'catalog/admin/pricegroup'
    )]
    public function list()
    {
        return $this->view(
            $this->templatePath . '/PriceGroup/price_group_list.twig',
            $this->getContext($this->container->get(PriceGroupList::class))
        );
    }

    #[Route(
        path: 'admin/catalog/pricegroup/add',
        name: 'catalog/admin/pricegroup/add'
    )]
    public function add()
    {
        return $this->view(
            $this->templatePath . '/PriceGroup/price_group_add.twig',
            $this->getContext($this->container->get(PriceGroupAdd::class))
        );
    }

    #[Route(
        path: 'admin/catalog/pricegroup/edit',
        name: 'catalog/admin/pricegroup/edit'
    )]
    public function edit()
    {
        return $this->view(
            $this->templatePath . '/PriceGroup/price_group_edit.twig',
            $this->getContext($this->container->get(PriceGroupEdit::class))
        );
    }

    #[Route(
        path: 'admin/catalog/pricegroup/delete',
        name: 'catalog/admin/pricegroup/delete'
    )]
    public function delete()
    {
        return $this->view(
            $this->templatePath . '/PriceGroup/price_group_delete.twig',
            $this->getContext($this->container->get(PriceGroupDelete::class))
        );
    }
}