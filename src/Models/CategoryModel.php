<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\ServerRequestWrapper;
use Enjoys\Traits\Options;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\DynamicConfig;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPriceEntityListener;
use EnjoysCMS\Module\Catalog\ORM\Doctrine\Functions\ConvertPrice;
use EnjoysCMS\Module\Catalog\Repositories;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryModel implements ModelInterface
{

    use Options;

    private Repositories\Category|ObjectRepository|EntityRepository $categoryRepository;

    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;
    private Category $category;
    private ?string $sortMode;

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private BreadcrumbsInterface $breadcrumbs,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config,
        private DynamicConfig $dynamicConfig,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $category = $this->getCategory(
            $this->requestWrapper->getAttributesData()->get('slug', '')
        );


        if ($category === null) {
            throw new NotFoundException(
                sprintf('Not found by slug: %s', $this->requestWrapper->getAttributesData()->get('slug', ''))
            );
        }

        $this->category = $category;

        $entityListenerResolver = $this->em->getConfiguration()->getEntityListenerResolver();
        $entityListenerResolver->register(new ProductPriceEntityListener($dynamicConfig));

        $this->em->getConfiguration()->addCustomStringFunction('CONVERT_PRICE', ConvertPrice::class);

        $this->sortMode = $this->getAndSetSortMode();
        $this->setOptions($this->config->getModuleConfig()->getAll());

//        $this->dynamicConfig->setCurrencyCode($this->requestWrapper->getQueryData('currency'));
    }


    public function getContext(): array
    {
        $pagination = new Pagination(
            $this->requestWrapper->getAttributesData()->get('page', 1),
            $this->getOption('limitItems')
        );

        if ($this->getOption('showSubcategoryProducts', false)) {
            $allCategoryIds = $this->em->getRepository(Category::class)->getAllIds($this->category);
            $qb = $this->productRepository->getFindByCategorysIdsDQL($allCategoryIds);
        } else {
            $qb = $this->productRepository->getQueryBuilderFindByCategory($this->category);
        }

        $qb->andWhere('p.hide = false');
        $qb->andWhere('p.active = true');

        if (false !== $o = $this->getOption('withImageFirst', false)) {
            $qb->orderBy('i.filename', strtoupper($o));
        }

        $qb->addSelect('CONVERT_PRICE(pr.price, pr.currency, :current_currency) as HIDDEN converted_price');
        $qb->setParameter('current_currency', $this->dynamicConfig->getCurrentCurrencyCode());

        switch ($this->sortMode) {
            case 'price.desc':
                $qb->addOrderBy('converted_price', 'DESC');
                break;
            case 'price.asc':
                $qb->addOrderBy('converted_price', 'ASC');
                break;
            case 'name.desc':
                $qb->addOrderBy('p.name', 'DESC');
                break;
            case 'name.asc':
            default:
                $qb->addOrderBy('p.name', 'ASC');
                break;
        }


        $qb->setFirstResult($pagination->getOffset())->setMaxResults($pagination->getLimitItems());

        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());

        return [
            '_title' => sprintf(
                '%2$s #страница %3$d - %1$s',
                Setting::get('sitename'),
                $this->category->getFullTitle(reverse: true) ?? 'Каталог',
                $pagination->getCurrentPage()
            ),
            'category' => $this->category,
            'categoryRepository' => $this->categoryRepository,
            'pagination' => $pagination,
            'products' => $result,
            'breadcrumbs' => $this->getBreadcrumbs(),
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getCategory(string $slug): ?Category
    {
        return $this->categoryRepository->findByPath($slug);
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

    /**
     * Allowed sort mode:
     * - price.desc
     * - price.asc
     * - name.desc
     * - name.asc [default]
     */
    private function getAndSetSortMode(): ?string
    {
        $mode = $this->requestWrapper->getQueryData('sort');
        if ($mode !== null){
            $this->dynamicConfig->setSortMode($mode);
        }

        return $this->dynamicConfig->getSortMode();
    }
}
