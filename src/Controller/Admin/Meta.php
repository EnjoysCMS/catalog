<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use EnjoysCMS\Module\Catalog\Crud\Product\Meta\MetaManage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/product/meta',
    name: '@a/catalog/product/meta',
    options: [
        'aclComment' => 'Управление Meta-tags'
    ]
)]
final class Meta extends AdminController
{


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(): string
    {
        return $this->view(
            $this->templatePath . '/meta.twig',
            $this->getContext($this->getContainer()->get(MetaManage::class))
        );
    }
}
