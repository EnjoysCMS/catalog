<?php

namespace EnjoysCMS\Module\Catalog\Admin\Filters;

use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(
    path: 'admin/catalog/filters',
    name: '@catalog_filters',
    comment: 'Управление Фильтрами'
)]
class Manage  extends AdminController
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
