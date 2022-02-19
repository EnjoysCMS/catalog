<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Crud\Product\Meta\MetaManage;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/product/meta', name: '@a/catalog/product/meta', options: ['aclComment' => 'Управление Meta-tags']
)]
final class Meta extends BaseController
{
    private string $templatePath;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->templatePath = Template::getAdminTemplatePath();
        $this->getTwig()->getLoader()->addPath($this->templatePath, 'catalog_admin');
    }

    public function __invoke()
    {
        return $this->view(
            $this->templatePath . '/meta.twig',
            $this->getContext($this->getContainer()->get(MetaManage::class))
        );
    }
}
