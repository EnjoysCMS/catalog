<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
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
            'comment' => '[public] Просмотр категорий'
        ],
        defaults: [
            'page' => 1,
            'slug' => ''
        ],
        priority: 1
    )]
    public function view(Container $container): ResponseInterface
    {
        $template_path = '@m/catalog/category.twig';


        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category.twig';
        }

        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $category */
        $category = $container
            ->get(EntityManager::class)
            ->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class)
            ->findByPath(
                $this->request->getAttribute('slug', '')
            ) ?? throw new \EnjoysCMS\Core\Exception\NotFoundException(
            sprintf('Not found by slug: %s', $this->request->getAttribute('slug', ''))
        );

        return $this->responseText(
            $this->twig->render(
                $category->getCustomTemplatePath() ?: $template_path,
                $container->get(CategoryModel::class)->getContext()
            )
        );
    }

}
