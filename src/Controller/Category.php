<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use Enjoys\Traits\Options;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Models\CategoryModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
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
    public function view(ServerRequestInterface $serverRequest): string
    {
        if ($serverRequest->get('slug') === '') {
            return $this->container->make(Index::class)->view();
        }


        $template_path = '@m/catalog/category.twig';


        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category.twig';
        }

        return $this->twig->render(
            $template_path,
            $this->container->make(CategoryModel::class, [
                'config' => Config::getConfig($this->container)->getAll()
            ])->getContext()
        );
    }

}
