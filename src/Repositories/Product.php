<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;

final class Product extends EntityRepository
{

    public function findBySlug(array $slugs)
    {
        $slug = array_shift($slugs);
        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
        $product = $this->findOneBy(['url' => $slug]);
        if($product === null || !$product->getCategory()->checkSlugs($slugs)){
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