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
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
            ;
    }


    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findBySlug(string $slugs)
    {
        $slugs = explode('/', $slugs);
        $slug = array_pop($slugs);
        /** @var  \EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository */
        $categoryRepository = $this->getEntityManager()->getRepository(
            Category::class
        )
        ;
        $category = $categoryRepository->findByPath(implode("/", $slugs));


        $dql = $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id')
            ->where('p.category = :category')
            ->setParameter('category', $category)
            ->andWhere('p.url = :url')
            ->setParameter('url', $slug)
        ;

        $product = $dql->getQuery()->getOneOrNullResult();

        if ($product === null) {
            return null;
        }

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

    public function findByCategory(Category $category)
    {
        return $this->getQueryFindByCategory($category)->getResult();
    }

    public function getQueryFindByCategory(Category $category): Query
    {
        return $this->getQueryBuilderFindByCategory($category)->getQuery();
    }

    public function getQueryBuilderFindByCategory(Category $category): QueryBuilder
    {
        return $this->getFindAllBuilder()
            ->where('p.category = :category')
            ->setParameter('category', $category)
            ;
    }


    public function findByCategorysIds($categoryIds)
    {
        $dql = $this->getFindAllBuilder()
            ->where('p.category IN (:category)')
            ->setParameter('category', $categoryIds)
        ;
        return $dql->getQuery()->getResult();
    }


}