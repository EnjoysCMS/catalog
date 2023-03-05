<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Module\Catalog\Entities\Category;

final class Product extends EntityRepository
{

    public function getFindAllBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i', 'm', 'u', 'q', 'pr')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.meta', 'm')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.quantity', 'q')
            ->leftJoin('p.prices', 'pr')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
        ;
    }


    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findBySlug(string $slugs): \EnjoysCMS\Module\Catalog\Entities\Product|null
    {
        $slugs = explode('/', $slugs);
        $slug = array_pop($slugs);

        /** @var  \EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository */
        $categoryRepository = $this->getEntityManager()->getRepository(
            Category::class
        );

        $category = $categoryRepository->findByPath(implode("/", $slugs));

        $dql = $this->getFindByUrlBuilder($slug, $category);

        $dql->andWhere('p.active = true');

        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
        $product = $dql->getQuery()->getOneOrNullResult();

        if ($product === null) {
            return null;
        }
        $product->setCurrentUrl(
            $product->getUrls()->filter(function ($item) use ($slug) {
                if ($item->getPath() === $slug) {
                    return true;
                }
                return false;
            })->current()
        );
        return $product;
    }


    public function getFindAllQuery(): Query
    {
        return $this->getFindAllBuilder()->getQuery();
    }

    public function findAll(): array
    {
        return $this->getFindAllQuery()->getResult();
    }

    public function like(string $query)
    {
        return $this->getFindAllBuilder()
            ->andWhere('p.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByCategory(Category $category)
    {
        return $this->getQueryFindByCategory($category)->getResult();
    }

    public function getQueryFindByCategory(?Category $category): Query
    {
        return $this->getQueryBuilderFindByCategory($category)->getQuery();
    }

    public function getQueryBuilderFindByCategory(?Category $category): QueryBuilder
    {
        if ($category === null) {
            return $this->getFindAllBuilder()->where('p.category IS NULL');
        }
        return $this->getFindAllBuilder()
            ->where('p.category = :category')
            ->setParameter('category', $category)
        ;
    }

    public function getFindByCategorysIdsDQL($categoryIds): QueryBuilder
    {
        $qb = $this->getFindAllBuilder();

        $qb->where('p.category IN (:category)')
            ->setParameter('category', $categoryIds)
        ;

        if (false !== $null_key = array_search(null, $categoryIds)) {
            $qb->orWhere('p.category IS NULL');
        }

        return $qb;
    }

    public function getFindByCategorysIdsQuery($categoryIds): Query
    {
        return $this->getFindByCategorysIdsDQL($categoryIds)->getQuery();
    }

    public function findByCategorysIds($categoryIds)
    {
        return $this->getFindByCategorysIdsQuery($categoryIds)->getResult();
    }

    public function getFindByIdsDQL($productIds): QueryBuilder
    {
        return $this->getFindAllBuilder()
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $productIds)
        ;
    }

    public function getFindByIdsQuery($productIds): Query
    {
        return $this->getFindByIdsDQL($productIds)->getQuery();
    }

    public function findByIds($productIds)
    {
        return $this->getFindByIdsQuery($productIds)->getResult();
    }

    public function getFindByUrlBuilder(string $url, ?Category $category = null): QueryBuilder
    {
        $dql = $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i', 'f')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i')
            ->leftJoin('p.files', 'f')
            ->orderBy('i.general', 'desc')
        ;
        if ($category === null) {
            $dql->where('p.category IS NULL');
        } else {
            $dql->where('p.category = :category')
                ->setParameter('category', $category)
            ;
        }
        $dql->leftJoin('p.urls', 'u')
            ->andWhere('u.path = :url')
            ->setParameter('url', $url)
        ;

        return $dql;
    }
}
