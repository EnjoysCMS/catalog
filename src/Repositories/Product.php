<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;

final class Product extends EntityRepository
{

    public function findBySlug(array $slugs)
    {
        $slug = array_shift($slugs);
        $category = $this->getEntityManager()->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class)->findBySlug($slugs);
        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
        $product = $this->findOneBy(['url' => $slug, 'category' => $category]);
        if($product === null){
            return null;
        }
        return $product;
    }

    public function findAll(): array
    {
        $dbl = $this->createQueryBuilder('p')
            ->select('p', 'c', 'tree_parent')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 'tree_parent')
        ;

        return $dbl->getQuery()
            ->getResult()
            ;
    }

}