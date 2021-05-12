<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\Query\Expr;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class Category extends ClosureTreeRepository
{

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
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
     * @param string $slugs
     * @return object|null
     * @deprecated use findByPath
     */
    public function findBySlug(string $slugs)
    {
        $slugs = array_reverse(explode('/', $slugs));
        $slug = array_shift($slugs);
        $category = $this->findOneBy(['url' => $slug]);

        if ($category === null) {
            return null;
        }


        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $parent */
        $parent = $category->getParent();
        if ($parent !== null && !$parent->checkSlugs($slugs)) {
            return null;
        }

        if ($parent === null && !empty($slugs)) {
            return null;
        }

        return $category;
    }

    public function getTree($node = null)
    {
        return $this->childrenHierarchy(
            $node,
            false,
            [
                'childSort' => [
                    'field' => 'sort',
                    'dir' => 'asc'
                ]
            ]
        );
    }

    public function getNodes($node = null, $orderBy = 'sort', $direction = 'asc')
    {
        $dql = $this->childrenQueryBuilder($node, true, $orderBy, $direction);
        // $dql->leftJoin('node.children', 'child');
        //  var_dump($dql->getQuery());
        return $dql->getQuery()->getResult();
    }

    public function getFormFillArray(): array
    {
        return $this->_build($this->getNodes());
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