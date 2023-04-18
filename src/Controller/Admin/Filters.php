<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(
    path: 'admin/catalog/filters',
    name: 'catalog/admin/filters',
    options: [
        'comment' => 'Управление Фильтрами'
    ]
)]
class Filters extends AdminController
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
                        $urlGenerator->generate('catalog/admin/filters') => 'Фильтры (настройка)',
                    ],
                ]
            )
        );
    }
}
