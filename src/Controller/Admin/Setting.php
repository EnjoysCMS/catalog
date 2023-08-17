<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/setting',
    name: 'catalog/admin/setting'
)]
final class Setting extends AdminController
{

    public function __invoke(\EnjoysCMS\Module\Catalog\Crud\Setting $setting): ResponseInterface
    {
        return $this->response($this->twig->render(
            $this->templatePath . '/setting.twig',
            $setting->getContext()
        ));
    }

}
