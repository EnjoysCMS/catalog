<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: "admin/catalog",
    name: '@a/catalog/dashboard'
)]
final class Dashboard extends AdminController
{
    public function __invoke(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/dashboard.twig',
            [])
        );
    }
}
