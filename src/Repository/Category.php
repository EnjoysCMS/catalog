<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repository;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Module\Catalog\Entity\CategoryClosure;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

/**
 * @method \EnjoysCMS\Module\Catalog\Entity\Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method \EnjoysCMS\Module\Catalog\Entity\Category[] findAll()
 * @method \EnjoysCMS\Module\Catalog\Entity\Category[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Category extends ClosureTreeRepository
{

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        if (empty($id)) {
            return null;
        }
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByPath(?string $path): ?\EnjoysCMS\Module\Catalog\Entity\Category
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
                \EnjoysCMS\Module\Catalog\Entity\Category::class,
                $alias,
                Expr\Join::WITH,
                "{$alias}.parent = $parentJoin AND {$alias}.url = :url{$k} AND {$alias}.status = true",
            );

            $parameters['url' . $k] = $slug;

            $parentJoin = $alias . '.id';
        }
        //$dql->andWhere("{$alias}.status = true");
        $dql->select($alias);

        $dql->setParameters($parameters);

        $dql->addSelect('m');
        $dql->leftJoin("$alias.meta", 'm', Expr\Join::WITH, "m.category = $alias.id");

        $query = $dql->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @return list<\EnjoysCMS\Module\Catalog\Entity\Category>
     * @throws NonUniqueResultException
     * @throws NoResultException
     * *@throws \Exception
     * @throws QueryException
     */
    public function getChildNodesWithCountProducts(
        $node = null,
        array $criteria = [],
        string $orderBy = 'sort',
        string $direction = 'asc',
    ): array {
        $qb = $this->getChildNodesQueryBuilder($node, $criteria, $orderBy, $direction);
        // Подзапрос для подсчета продуктов через closure
        $subQuery = $this
            ->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p2.id)')
            ->from(CategoryClosure::class, 'cl2')
            ->join(\EnjoysCMS\Module\Catalog\Entity\Product::class, 'p2', 'WITH', 'p2.category = cl2.descendant')
            ->where('cl2.ancestor = node.id');

        // Добавляем подзапрос как скалярное поле
        $qb->addSelect('(' . $subQuery->getDQL() . ') as products_count');

        $result = $qb
            ->getQuery()
            ->getResult();


        $categories = $this->hydrateCategoriesWithCounts($result);

        foreach ($categories as $category) {
            $this->addCountsToChildren($category);
        }

        return $categories;
    }

    private function hydrateCategoriesWithCounts(array $result): array
    {
        $categories = [];

        foreach ($result as $item) {
            if (is_array($item) && isset($item[0]) && $item[0] instanceof \EnjoysCMS\Module\Catalog\Entity\Category) {
                $category = $item[0];
                $category->setProductsCount((int)($item['products_count'] ?? 0));
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * @throws \Exception
     */
    private function addCountsToChildren(\EnjoysCMS\Module\Catalog\Entity\Category $category): void
    {
        $childrenIds = [];
        foreach ($category->getChildren() as $child) {
            $childrenIds[] = $child->getId();
        }

        if (empty($childrenIds)) {
            return;
        }


        $counts = $this
            ->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(cl.ancestor) as category_id, COUNT(p.id) as products_count')
            ->from(\EnjoysCMS\Module\Catalog\Entity\CategoryClosure::class, 'cl')
            ->join(\EnjoysCMS\Module\Catalog\Entity\Product::class, 'p', 'WITH', 'p.category = cl.descendant')
            ->where('cl.ancestor IN (:ids)')
            ->setParameter('ids', $childrenIds)
            ->groupBy('cl.ancestor')
            ->getQuery()
            ->getResult();


        $countMap = [];
        foreach ($counts as $count) {
            $countMap[$count['category_id']] = (int)$count['products_count'];
        }

        /** @var \EnjoysCMS\Module\Catalog\Entity\Category $child */
        foreach ($category->getChildren() as $child) {
            $child->setProductsCount($countMap[$child->getId()] ?? 0);
            $this->addCountsToChildren($child);
        }
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @return list<\EnjoysCMS\Module\Catalog\Entity\Category>
     */
    public function getChildNodes(
        $node = null,
        array $criteria = [],
        string $orderBy = 'sort',
        string $direction = 'asc',
    ): array {
        return $this
            ->getChildNodesQuery($node, $criteria, $orderBy, $direction)
//            ->setFetchMode(Category::class, 'children', ClassMetadata::FETCH_EAGER)
            ->getResult();
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getChildNodesQuery(
        $node = null,
        array $criteria = [],
        string $orderBy = 'sort',
        string $direction = 'asc',
    ): Query {
        return $this->getChildNodesQueryBuilder($node, $criteria, $orderBy, $direction)->getQuery();
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getChildNodesQueryBuilder(
        $node = null,
        array $criteria = [],
        string $orderBy = 'sort',
        string $direction = 'asc',
    ): QueryBuilder {
        $currentLevel = 0;

        $maxLevel = $this
            ->createQueryBuilder('c')
            ->select('max(c.level)')
            ->getQuery()
            ->getSingleScalarResult();

        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        $dql = $this->getQueryBuilder();
        if ($node === null) {
            $dql
                ->select('node')
                ->from($config['useObjectClass'], 'node')
                ->where('node.' . $config['parent'] . ' IS NULL');
        } else {
            $currentLevel = $this
                ->createQueryBuilder('c')
                ->select('c.level')
                ->where('c.id = :node')
                ->setParameter('node', $node)
                ->getQuery()
                ->getSingleScalarResult();

            $dql
                ->select('node')
                ->from($config['useObjectClass'], 'node')
                ->where('node.' . $config['parent'] . ' = :node')
                ->setParameter('node', $node);
        }

        if ($meta->hasField($orderBy) && in_array(strtolower($direction), ['asc', 'desc'])) {
            $dql->orderBy('node.' . $orderBy, $direction);
        } else {
            throw new InvalidArgumentException(
                "Invalid sort options specified: field - {$orderBy}, direction - {$direction}",
            );
        }


        foreach ($criteria as $field => $value) {
            if ($value instanceof Criteria) {
                $dql->addCriteria($value);
                continue;
            }
            $dql->addCriteria(Criteria::create()->where(Criteria::expr()->eq($field, $value)));
        }

        $parentAlias = 'node';
        for ($i = $currentLevel + 2; $i <= $maxLevel + 1; $i++) {
            $condition = "c{$i}.level = $i and c{$i}.parent = {$parentAlias}.id";
            foreach ($criteria as $field => $value) {
                if ($value instanceof Criteria) {
                    /** @var Comparison $expr */
                    $expr = $value->getWhereExpression()->visit(
                        new Query\QueryExpressionVisitor(["c{$i}"]),
                    );
                    $condition .= sprintf(
                        ' AND %s %s %s',
                        $expr->getLeftExpr(),
                        $expr->getOperator(),
                        $expr->getRightExpr(),
                    );
                    continue;
                }
                $condition .= " AND c{$i}.{$field} = :{$field}";
                // параметры биндятся автоматически, чудеса )
                // $parameters[$field] = $value;
            }

            $dql->addOrderBy("c{$i}.{$orderBy}", $direction);

            $dql->leftJoin(
                "{$parentAlias}.children",
                "c{$i}",
                Expr\Join::WITH,
                $condition,
            );
            $dql->addSelect("c{$i}");

            // join category_meta (\EnjoysCMS\Module\Catalog\Entities\CategoryMeta)
            $dql->leftJoin(
                "{$parentAlias}.meta",
                "m{$i}",
            );
            $dql->addSelect("m{$i}");

            $parentAlias = "c{$i}";
        }

        return $dql;
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getFormFillArray(
        $node = null,
        array $criteria = [],
        string $orderBy = 'sort',
        string $direction = 'asc',
    ): array {
        return $this->_build($this->getChildNodes($node, $criteria, $orderBy, $direction));
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

    public function getAllIds($node = null): array
    {
        /** @var \EnjoysCMS\Module\Catalog\Entity\Category[] $nodes */
        $nodes = $this->getChildren($node);
        $ids = array_filter(
            array_map(
                function ($node) {
                    if (!$node->isStatus()) {
                        return null;
                    }
                    return $node?->getId();
                },
                $nodes,
            ),
        );
        $ids[] = $node?->getId();
        return $ids;
    }

    public function getCountProducts($node = null)
    {
        $nodes = $this->getAllIds($node);
        return $this
            ->getEntityManager()->createQueryBuilder()
            ->select('count(p.id)')
            ->from(\EnjoysCMS\Module\Catalog\Entity\Product::class, 'p')
            ->where('p.category IN (:ids)')
            ->setParameter('ids', $nodes)
            ->getQuery()->getSingleScalarResult();
    }

}
