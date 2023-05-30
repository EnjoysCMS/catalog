<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\ProductPrice;
use EnjoysCMS\Module\Catalog\Helpers\Normalize;

class PriceFilter implements \EnjoysCMS\Module\Catalog\Filters\FilterInterface
{

    public function __construct(private EntityManager $em, private Config $config, private array $currentValues = [])
    {
    }

    public function getType(): string
    {
        return 'price';
    }

    public function getParamName(): int|string
    {
        return 'price';
    }

    public function getFormName(): string
    {
        return 'filter[price]';
    }

    public function getTitle(): string
    {
        return 'Фильтр по цене';
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getPossibleValues(array $pids): array
    {
        /** @var array<array-key, int|float> $minmaxRaw */
        $minmaxRaw = $this->em
            ->createQueryBuilder()
            ->select(
                'MIN(CONVERT_PRICE(pr.price, pr.currency, :current_currency)) as min, MAX(CONVERT_PRICE(pr.price, pr.currency, :current_currency)) as max'
            )
            ->from(ProductPrice::class, 'pr')
            ->where('pr.product IN (:pids)')
            ->setParameters([
                'pids' => $pids,
                'current_currency' => $this->config->getCurrentCurrencyCode()
            ])
            ->getQuery()
            ->getOneOrNullResult();

        return array_map(function ($value) {
            return (int)ceil(Normalize::intPriceToFloat($value));
        }, $minmaxRaw, []);
    }

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder
    {
//        return $qb;
        return $qb->andWhere('CONVERT_PRICE(pr.price, pr.currency, :current_currency) BETWEEN :minPrice AND :maxPrice')
            ->setParameter('current_currency', $this->config->getCurrentCurrencyCode())
            ->setParameter('minPrice', Normalize::floatPriceToInt($this->currentValues['min']))
            ->setParameter('maxPrice', Normalize::floatPriceToInt($this->currentValues['max']));
    }
}
