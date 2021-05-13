<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryException;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class Category extends ClosureTreeRepository
{

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findByPath(string $path)
    {
        $slugs = explode('/', $path);
        $first = array_shift($slugs);
        $alias = 'c';
        $dql = $this->createQueryBuilder($alias);

        $parameters = ['url' => $first];

        $dql->where("{$alias}.parent IS NULL AND {$alias}.url = :url  AND {$alias}.status = true");
        $parentJoin = "{$alias}.id";

        foreach ($slugs as $k => $slug) {
            $alias = $alias . $k;
            //
            $dql->leftJoin(
                \EnjoysCMS\Module\Catalog\Entities\Category::class,
                $alias,
                Expr\Join::WITH,
                "{$alias}.parent = $parentJoin AND {$alias}.url = :url{$k} AND {$alias}.status = true"
            );

            $parameters['url' . $k] = $slug;

            $parentJoin = $alias . '.id';
        }
        //$dql->andWhere("{$alias}.status = true");
        $dql->select($alias);

        $dql->setParameters($parameters);

        $query = $dql->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param null $node
     * @param array $criteria
     * @param string $orderBy
     * @param string $direction
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    public function getChildNodes($node = null, array $criteria = [], string $orderBy = 'sort', string $direction = 'asc')
    {
        $currentLevel = 0;
        $maxLevel = $this->createQueryBuilder('c')
            ->select('max(c.level)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        $dql = $this->getQueryBuilder();
        if ($node === null) {
            $dql->select('node')
                ->from($config['useObjectClass'], 'node')
                ->where('node.' . $config['parent'] . ' IS NULL')
            ;
        } else {
            $currentLevel = $this->createQueryBuilder('c')
                ->select('c.level')
                ->where('c.id = :node')
                ->setParameter('node', $node)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $dql->select('node')
                ->from($config['useObjectClass'], 'node')
                ->where('node.' . $config['parent'] . ' = :node')
                ->setParameter('node', $node)
            ;
        }

        if ($meta->hasField($orderBy) && in_array(strtolower($direction), ['asc', 'desc'])) {
            $dql->orderBy('node.' . $orderBy, $direction);
        } else {
            throw new InvalidArgumentException(
                "Invalid sort options specified: field - {$orderBy}, direction - {$direction}"
            );
        }




        foreach ($criteria as $field => $value) {
            $dql->addCriteria(Criteria::create()->where(Criteria::expr()->eq($field, $value)));
        }

        $parentAlias = 'node';
        for ($i = $currentLevel + 2; $i <= $maxLevel; $i++) {
            $condition = "c{$i}.level = $i and c{$i}.parent = {$parentAlias}.id";
            foreach ($criteria as $field => $value) {
                $condition .= " AND c{$i}.{$field} = :{$field}";
                // параметры биндятся автоматически, чудеса )
                // $parameters[$field] = $value;
            }
            $dql->leftJoin(
                "{$parentAlias}.children",
                "c{$i}",
                Expr\Join::WITH,
                $condition
            );

            $parentAlias = "c{$i}";
            $dql->addSelect("c{$i}");
        }

        return $dql->getQuery()->getResult();
    }

    public function getFormFillArray(): array
    {
        return $this->_build($this->getChildNodes());
    }

    private function _build($tree, $level = 1): array
    {
        $ret = [];

        foreach ($tree as $item) {
            $ret[$item->getId()] = str_repeat("&nbsp;", ($level - 1) * 3) . $item->getTitle();
            if (count($item->getChildren()) > 0) {
                $ret += $this->_build($item->getChildren(), $item->getLevel() + 1);
            }
        }
        return $ret;
    }

    public function getAllIds($node = null)
    {
        $nodes = $this->getChildren($node);
        $ids = array_map(
            function ($node) {
                return $node->getId();
            },
            $nodes
        );
        $ids[] = $node->getId();
        return $ids;
    }

}