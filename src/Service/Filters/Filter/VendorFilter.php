<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Entity\Vendor;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterParams;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Checkbox;

class VendorFilter implements FilterInterface
{
    private string $name = 'Бренд (производитель)';

    public function __construct(
        private FilterParams $params,
        private EntityManager $em
    ) {
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getPossibleValues(array $productIds): array
    {
        /** @var array<int, Vendor|null> $vendors */
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
            $vendorName = $vendor?->getName();
            if (empty($vendorName)) {
                continue;
            }
            $values[$vendor->getId()] = $vendorName;
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
        (new Checkbox($form, $this, $values))->create();
        $elements = $form->getElements();
        $element = end($elements);
        $element->addAttribute(AttributeFactory::create('data-is-main', ($this->params->main ?? false) ? 'true' : 'false'));
        return $form;
    }

    public function getFormName(): string
    {
        return 'filter[vendor]';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
