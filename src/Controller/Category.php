<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Module\Catalog\Models\CategoryModel;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Category extends PublicController
{

    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: 'catalog/{slug}@{page}',
        name: 'catalog/category',
        requirements: [
            'slug' => '[^.^@]*',
            'page' => '\d+'
        ],
        options: [
            'aclComment' => '[public] Просмотр категорий'
        ],
        defaults: [
            'page' => 1,
            'slug' => ''
        ]
    )]
    public function view(Container $container): ResponseInterface
    {

        if ($this->request->getAttribute('slug', '') === '') {
            return $container->call([Index::class, 'view']);
        }

        $template_path = '@m/catalog/category.twig';


        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category.twig';
        }

        return $this->responseText($this->twig->render(
            $template_path,
            $container->get(CategoryModel::class)->getContext()
        ));
    }

}
