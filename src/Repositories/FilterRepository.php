<?php

namespace EnjoysCMS\Module\Catalog\Repositories;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Filter;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\ORM\Doctrine\FilterHydrate;

class FilterRepository extends EntityRepository
{

    public function getCategoryFilters(Category $category)
    {
        $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
            FilterHydrate::NAME,
            FilterHydrate::class
        );

        $qb = $this->createQueryBuilder('f')
            ->select('f, v, k')
            ->join(OptionKey::class, 'k', Expr\Join::WITH, 'k.id = f.optionKey')
            ->join(OptionValue::class, 'v', Expr\Join::WITH, 'k.id = v.optionKey')
            ->where('f.category = :category')
            ->andWhere(':pids MEMBER OF v.products')
//            ->groupBy('k.id')
            ->setParameters([
                'pids' => $this->getProductIdsFromCategory($category),
                'category' => $category
            ]);

        return $qb->getQuery()->getResult(FilterHydrate::NAME);
    }

    private function getProductIdsFromCategory(Category $category): array
    {
        return array_column(
            $this
                ->getEntityManager()
                ->createQueryBuilder()->select('p.id')
                ->from(Product::class, 'p')
                ->leftJoin('p.category', 'c')
                ->where('c.id = :category')
                ->distinct()
                ->setParameter('category', $category)
                ->getQuery()
                ->getArrayResult(),
            'id'
        );
    }

}
