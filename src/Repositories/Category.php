<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class Category extends ClosureTreeRepository
{

    public function findBySlug(array $slugs)
    {
        $slug = array_shift($slugs);
        $category = $this->findOneBy(['url' => $slug]);

        if ($category === null) {
            return null;
        }

        $parent = $category->getParent();
        if ($parent !== null && !$parent->checkSlugs($slugs)) {
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
        $dql->leftJoin('node.parent', 'tree_parent');
        return $dql->getQuery()->getResult();
    }

    public function getFormFillArray(): array
    {
        return $this->_build($this->getTree());
    }

    private function _build($tree, $level = 1): array
    {
        $ret = [];

        foreach ($tree as $items) {
            $ret[$items['id']] = str_repeat("&nbsp;", ($level - 1) * 3) . $items['title'];
            if (isset($items['__children'])) {
                $ret += $this->_build($items['__children'], $items['level'] + 1);
            }
        }
        return $ret;
    }

}