<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Module\Catalog\Crud\Product\Meta\MetaManage;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(MetaManage $metaManage): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/meta.twig',
                $metaManage->getContext()
            )
        );
    }
}
