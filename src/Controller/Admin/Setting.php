<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/setting',
    name: 'catalog/admin/setting'
)]
final class Setting extends BaseController
{
    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
        $this->getTwig()->getLoader()->addPath($this->templatePath, 'catalog_admin');
    }

    public function __invoke(): string
    {
        return $this->view(
            $this->templatePath . '/setting.twig',
            $this->getContext($this->container->get(\EnjoysCMS\Module\Catalog\Crud\Setting::class))
        );
    }

}
