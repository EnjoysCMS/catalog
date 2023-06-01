<?php

namespace EnjoysCMS\Module\Catalog\Filters\Controller;

use EnjoysCMS\Module\Catalog\Controller\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(
    path: 'admin/catalog/manage_filters',
    name: 'catalog/manage-filters',
    options: [
        'comment' => 'Управление Фильтрами'
    ]
)]
class ManageFilters  extends AdminController
{
    public function __invoke(UrlGeneratorInterface $urlGenerator): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/filters.twig',
                [
                    'breadcrumbs' => [
                        $urlGenerator->generate('admin/index') => 'Главная',
                        $urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                        'Фильтры (настройка)',
                    ],
                ]
            )
        );
    }
}
