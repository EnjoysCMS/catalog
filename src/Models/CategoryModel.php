<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use Enjoys\Traits\Options;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryModel implements ModelInterface
{

    use Options;

    private Repositories\Category|ObjectRepository|EntityRepository $categoryRepository;

    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;
    private Category $category;

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private BreadcrumbsInterface $breadcrumbs,
        private UrlGeneratorInterface $urlGenerator,
        array $config = []
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->category = $this->getCategory(
            $this->serverRequest->get('slug')
        );

        $this->setOptions($config);
    }


    #[ArrayShape([
        '_title' => "string",
        'category' => Category::class,
        'pagination' => Pagination::class,
        'products' => Paginator::class,
        'breadcrumbs' => "array"
    ])]
    public function getContext(): array
    {
        $pagination = new Pagination($this->serverRequest->get('page', 1), $this->getOption('limitItems'));

        if ($this->getOption('showSubcategoryProducts', false)) {
            $allCategoryIds = $this->em->getRepository(Category::class)->getAllIds($this->category);
            $qb = $this->productRepository->getFindByCategorysIdsQuery($allCategoryIds);
        } else {
            $qb = $this->productRepository->getQueryFindByCategory($this->category);
        }
        $qb->setFirstResult($pagination->getOffset())->setMaxResults($pagination->getLimitItems());
        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());

        return [
            '_title' => sprintf(
                '%2$s - %1$s',
                Setting::get('sitename'),
                $this->category->getFullTitle(reverse: true)
            ),
            'category' => $this->category,
            'pagination' => $pagination,
            'products' => $result,
            'breadcrumbs' => $this->getBreadcrumbs(),
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getCategory(string $slug): Category
    {
        $category = $this->categoryRepository->findByPath($slug);
        if ($category === null) {
            throw new NoResultException();
        }
        return $category;
    }

    private function getBreadcrumbs(): array
    {
        $this->breadcrumbs->add($this->urlGenerator->generate('catalog/index'), 'Каталог');
        foreach ($this->category->getBreadcrumbs() as $breadcrumb) {
            $this->breadcrumbs->add(
                $this->urlGenerator->generate('catalog/category', ['slug' => $breadcrumb['slug']]),
                $breadcrumb['title']
            );
        }

        return $this->breadcrumbs->get();
    }
}