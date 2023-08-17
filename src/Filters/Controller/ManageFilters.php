<?php

namespace EnjoysCMS\Module\Catalog\Filters\Controller;

use EnjoysCMS\Module\Catalog\Admin\AdminController;
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
        $this->breadcrumbs->setLastBreadcrumb('Фильтры (настройка)');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/filters.twig'
            )
        );
    }
}
