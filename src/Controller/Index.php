<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Index extends PublicController
{


    /**
     * @throws SyntaxError
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NoResultException
     */
    #[Route(
        path: 'catalog',
        name: 'catalog/index',
        options: ['comment' => '[PUBLIC] Просмотр категорий (индекс)'],
        priority: 2
    )]
    public function view(
        EntityManager $entityManager,
        BreadcrumbsInterface $breadcrumbs,
        Setting $setting
    ): ResponseInterface
    {
        /**
         * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository
         */
        $categoryRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);

        $breadcrumbs->add(null, 'Каталог');

        $template_path = '@m/catalog/category_index.twig';
        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category_index.twig';
        }

        return $this->responseText(
            $this->twig->render(
                $template_path,
                [
                    '_title' => sprintf(
                        '%2$s - %1$s',
                        $setting->get('sitename'),
                        'Каталог'
                    ),
                    'categories' => $categoryRepository->getChildNodes(null, ['status' => true]),
                    'categoryRepository' => $categoryRepository,
                    'breadcrumbs' => $breadcrumbs->get(),
                ]
            )
        );
    }
}
