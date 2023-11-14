<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Entity\Vendor;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterParams;

class VendorFilter implements FilterInterface
{
    public function __construct(
        private FilterParams $params,
        private EntityManager $em
    ) {
    }

    public function __toString(): string
    {
        return 'Фильтр по бренду (производителю)';
    }

    public function getPossibleValues(array $productIds): array
    {
        /** @var Vendor[] $vendors */
        $vendors = $this->em
            ->createQueryBuilder()
            ->select('v')
            ->from(Product::class, 'p')
            ->leftJoin(Vendor::class, 'v', Expr\Join::WITH, 'p.vendor = v')
            ->where('p.id IN (:pids)')
            ->setParameter('pids', $productIds)
            ->getQuery()
            ->getResult();

        $values = [];
        foreach ($vendors as $vendor) {
            $values[$vendor->getId()] = $vendor->getName();
        }
        return $values;
    }

    public function addFilterQueryBuilderRestriction(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere('p.vendor IN (:vendors)')
            ->setParameter('vendors', $this->params->getParams());
    }

    public function getFormElement($form, $values): Form
    {
        $form->checkbox('filter[vendor]', $this->__toString())
            ->fill($values);
        return $form;
    }
}
