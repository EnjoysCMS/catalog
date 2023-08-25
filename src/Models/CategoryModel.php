<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models;

use DI\DependencyException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Pagination\Pagination;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Entity\ProductPriceEntityListener;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use EnjoysCMS\Module\Catalog\ORM\Doctrine\Functions\ConvertPrice;
use EnjoysCMS\Module\Catalog\Repository;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryModel implements ModelInterface
{


    private Repository\Category|ObjectRepository|EntityRepository $categoryRepository;

    private Repository\Product|ObjectRepository|EntityRepository $productRepository;
    private Category $category;
    private Pagination $pagination;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly BreadcrumbCollection $breadcrumbs,
        private readonly RedirectInterface $redirect,
        private readonly FilterFactory $filterFactory,
        private readonly Config $config,
        private readonly Setting $setting,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);

        $this->category = $this->categoryRepository->findByPath(
            $this->request->getAttribute('slug', '')
        ) ?? throw new NotFoundException(
            sprintf('Not found by slug: %s', $this->request->getAttribute('slug', ''))
        );

        $this->pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $this->config->getPerPage()
        );

        $globalExtraFields = array_filter(
            array_map(function ($item) {
                return $this->em->getRepository(OptionKey::class)->find($item);
            }, explode(',', $setting->get('globalExtraFields', '')))
        );

        foreach ($globalExtraFields as $globalExtraField) {
            $this->category->addExtraField($globalExtraField);
        }

        $entityListenerResolver = $this->em->getConfiguration()->getEntityListenerResolver();
        $entityListenerResolver->register(new ProductPriceEntityListener($this->config));

        $this->em->getConfiguration()->addCustomStringFunction('CONVERT_PRICE', ConvertPrice::class);

        $this->updateConfigValues();

        $this->breadcrumbs->add('catalog/index', 'Каталог');
        foreach ($this->category->getBreadcrumbs() as $breadcrumb) {
            $this->breadcrumbs->add(
                ['catalog/category', ['slug' => $breadcrumb['slug']]],
                $breadcrumb['title']
            );
        }
    }


    /**
     * @throws NotFoundException
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function getContext(): array
    {
        if ($this->config->get('showSubcategoryProducts', false)) {
            $allCategoryIds = $this->categoryRepository->getAllIds($this->category);
            $qb = $this->productRepository->getFindByCategorysIdsDQL($allCategoryIds);
        } else {
            $qb = $this->productRepository->getQueryBuilderFindByCategory($this->category);
        }

        // Filter goods
        $filtered = false;
        $filtersQueryString = $this->request->getQueryParams()['filter'] ?? false;
        $usedFilters = [];
        if (!empty($filtersQueryString) && is_array($filtersQueryString)) {
            $filtered = true;
            $filters = $this->filterFactory->createFromQueryString($filtersQueryString);
            foreach ($filters as $filter) {
                $qb = $filter->addFilterQueryBuilderRestriction($qb);
            }
        }

        $qb->andWhere('p.hide = false');
        $qb->andWhere('p.active = true');

        if (false !== $o = $this->config->get('withImageFirst', false)) {
            $qb->orderBy('i.filename', strtoupper($o));
        }

        if (!in_array($this->em->getConnection()->getDatabasePlatform()::class, [SqlitePlatform::class])){
            $qb->addSelect('CONVERT_PRICE(pr.price, pr.currency, :current_currency) as HIDDEN converted_price');
            $qb->setParameter('current_currency', $this->config->getCurrentCurrencyCode());
        }else{
            $qb->addSelect('pr.price as HIDDEN converted_price');
        }


        match ($this->config->getSortMode()) {
            'price.desc' => $qb->addOrderBy('converted_price', 'DESC'),
            'price.asc' => $qb->addOrderBy('converted_price', 'ASC'),
            'name.desc' => $qb->addOrderBy('p.name', 'DESC'),
            'custom' => $this->addCustomOrderBy($qb),
            default => $qb->addOrderBy('p.name', 'ASC'),
        };

//        dd($qb->getQuery()->getSQL());

        $qb->setFirstResult($this->pagination->getOffset())->setMaxResults($this->pagination->getLimitItems());

        $result = new Paginator($qb);
        $this->pagination->setTotalItems($result->count());

        if ($this->config->get('redirectCategoryToProductIfIsOne', false) && $this->pagination->getTotalItems() === 1) {
            $redirectAllow = false;

            if ($this->config->get('allowRedirectForAllCategories', false)) {
                $redirectAllow = true;
            }

            if ($redirectAllow === false && in_array(
                    $this->category->getId(),
                    $this->config->get('categoriesIdForRedirectToProductIfIsOne', []),
                    true
                )) {
                $redirectAllow = true;
            }

            if ($redirectAllow === true) {
                $this->redirect->toRoute('catalog/product', [
                    'slug' => $result->getIterator()->current()->getSlug()
                ], 301, true);
            }
        }


        return [
            'category' => $this->category,
            'categoryRepository' => $this->categoryRepository,
            'pagination' => $this->pagination,
            'products' => $result,
            'config' => $this->config,
            'setting' => $this->setting,
            'breadcrumbs' => $this->breadcrumbs,
            'filtered' => $filtered,
            'usedFilters' => $usedFilters,
        ];
    }


    private function updateConfigValues(): void
    {
        $this->updatePerPage();
        $this->updateSortMode();
    }

    private function updateSortMode(): void
    {
        $mode = $this->request->getQueryParams()['sort'] ?? $this->request->getParsedBody()['sort'] ?? null;
        if ($mode !== null) {
            $this->config->setSortMode($mode);
        }
    }

    private function updatePerPage(): void
    {
        $perpage = $this->request->getQueryParams()['perpage'] ?? $this->request->getParsedBody()['perpage'] ?? null;
        if ($perpage !== null) {
            $this->config->setPerPage($perpage);
        }
    }

    private function addCustomOrderBy(QueryBuilder $qb): void
    {
        foreach (
            $this->config->get('customSortParams') ?? throw new \RuntimeException(
            'Need set customSortParams'
        ) as $order
        ) {
            $qb->addOrderBy($order['column'], $order['direction']);
        }
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

}
