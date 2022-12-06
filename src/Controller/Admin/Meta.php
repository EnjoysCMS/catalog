<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\Product\Meta\MetaManage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/product/meta',
    name: '@a/catalog/product/meta',
    options: [
        'comment' => 'Управление Meta-tags'
    ]
)]
final class Meta extends AdminController
{


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/meta.twig',
            $this->getContext($this->getContainer()->get(MetaManage::class))
        ));
    }
}
