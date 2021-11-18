<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\PriceGroupModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/pricegroup',
    name: 'catalog/admin/pricegroup'
)]
final class PriceGroupController extends BaseController
{
    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    public function __invoke()
    {
        return $this->view(
            $this->templatePath . '/price_group.twig',
            $this->getContext($this->container->get(PriceGroupModel::class))
        );
    }
}