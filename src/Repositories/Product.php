<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use EnjoysCMS\Module\Catalog\Entities\Image;

final class Product extends EntityRepository
{

    public function findBySlug(string $slugs)
    {
        $slugs = explode('/', $slugs);
        $slug = array_pop($slugs);
        /** @var Category $categoryRepository */
        $categoryRepository = $this->getEntityManager()->getRepository(
            \EnjoysCMS\Module\Catalog\Entities\Category::class
        )
        ;
        $category = $categoryRepository->findByPath(implode("/", $slugs));

        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
//        $product = $this->findOneBy(['url' => $slug, 'category' => $category]);
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

        $product = $dql->getQuery()->getSingleResult();

        if ($product === null) {
            return null;
        }
        return $product;
    }

    public function findByCategory($category)
    {
        $dql = $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
            ->where('p.category = :category')
            ->setParameter('category', $category)
        ;
        return $dql->getQuery()->getResult();
    }

    public function findByCategorysIds($categoryIds)
    {
        $dql = $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
            ->where('p.category IN (:category)')
            ->setParameter('category', $categoryIds)
        ;
        return $dql->getQuery()->getResult();
    }


    public function findAll(): array
    {
        $dbl = $this->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
        ;
        $query = $dbl->getQuery();

        return $query->getResult();
    }

}