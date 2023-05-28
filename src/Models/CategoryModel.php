<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Traits\Options;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPriceEntityListener;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use EnjoysCMS\Module\Catalog\ORM\Doctrine\Functions\ConvertPrice;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryModel implements ModelInterface
{

    use Options;

    private Repositories\Category|ObjectRepository|EntityRepository $categoryRepository;

    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;
    private Category $category;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private BreadcrumbsInterface $breadcrumbs,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
        private Config $config,
        private Setting $setting,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);

        $this->category = $this->categoryRepository->findByPath(
            $this->request->getAttribute('slug', '')
        ) ?? throw new NotFoundException(
            sprintf('Not found by slug: %s', $this->request->getAttribute('slug', ''))
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
        $this->setOptions($this->config->get());
    }


    /**
     * @throws NotFoundException
     */
    public function getContext(): array
    {
        $pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $this->config->getPerPage()
        );

        if ($this->getOption('showSubcategoryProducts', false)) {
            $allCategoryIds = $this->categoryRepository->getAllIds($this->category);
            $qb = $this->productRepository->getFindByCategorysIdsDQL($allCategoryIds);
        } else {
            $qb = $this->productRepository->getQueryBuilderFindByCategory($this->category);
        }

        // Filter goods
        $filtered = false;
        $filters = $this->request->getQueryParams()['filter'] ?? false;
        if (!empty($filters) && is_array($filters)) {
            $filtered = true;
            $i = 0;
            foreach ($filters as $value) {
                $qb->andWhere(sprintf(':value%s MEMBER OF p.options', $i))
                    ->setParameter('value' . $i, $value);
                $i++;
            }
        }

        $qb->andWhere('p.hide = false');
        $qb->andWhere('p.active = true');

        if (false !== $o = $this->getOption('withImageFirst', false)) {
            $qb->orderBy('i.filename', strtoupper($o));
        }

        $qb->addSelect('CONVERT_PRICE(pr.price, pr.currency, :current_currency) as HIDDEN converted_price');
        $qb->setParameter('current_currency', $this->config->getCurrentCurrencyCode());

        match ($this->config->getSortMode()) {
            'price.desc' => $qb->addOrderBy('converted_price', 'DESC'),
            'price.asc' => $qb->addOrderBy('converted_price', 'ASC'),
            'name.desc' => $qb->addOrderBy('p.name', 'DESC'),
            'custom' => $this->addCustomOrderBy($qb),
            default => $qb->addOrderBy('p.name', 'ASC'),
        };

//        dd($qb->getQuery()->getSQL());

        $qb->setFirstResult($pagination->getOffset())->setMaxResults($pagination->getLimitItems());

        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());

        if ($this->config->get('redirectCategoryToProductIfIsOne', false) && $pagination->getTotalItems() === 1) {
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
            '_title' => sprintf(
                '%2$s #страница %3$d - %1$s',
                $this->setting->get('sitename'),
                $this->category->getFullTitle(reverse: true) ?? 'Каталог',
                $pagination->getCurrentPage()
            ),
            'category' => $this->category,
            'categoryRepository' => $this->categoryRepository,
            'pagination' => $pagination,
            'products' => $result,
            'config' => $this->config,
            'breadcrumbs' => $this->getBreadcrumbs(),
            'filtered' => $filtered,
            'filters' => $filters
        ];
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
        $perpage = $this->request->getQueryParams()['perpage'] ?? null;
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


}
