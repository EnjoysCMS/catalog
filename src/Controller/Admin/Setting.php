<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/setting',
    name: 'catalog/admin/setting'
)]
final class Setting extends AdminController
{

    public function __invoke(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/setting.twig',
            $this->getContext($this->container->get(\EnjoysCMS\Module\Catalog\Crud\Setting::class))
        ));
    }

}
