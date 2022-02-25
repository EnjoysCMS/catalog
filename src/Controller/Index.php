<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Setting;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Index extends PublicController
{

    private BreadcrumbsInterface $breadcrumbs;
    private EntityManager $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->entityManager = $this->container->get(EntityManager::class);
        $this->breadcrumbs = $this->container->get(BreadcrumbsInterface::class);

    }


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
        options: ['comment' => '[PUBLIC] Просмотр категорий (индекс)']
    )]
    public function view(): string
    {
        /**
         * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository
         */
        $categoryRepository = $this->entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);

        $this->breadcrumbs->add(null, 'Каталог');

        $template_path = '@m/catalog/category_index.twig';
        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category_index.twig';
        }

        return $this->twig->render(
            $template_path,
            [
                '_title' => sprintf(
                    '%2$s - %1$s',
                    Setting::get('sitename'),
                    'Каталог'
                ),
                'categories' => $categoryRepository->getChildNodes(null, ['status' => true]),
                'categoryRepository' => $categoryRepository,
                'breadcrumbs' => $this->breadcrumbs->get(),
            ]
        );
    }
}
