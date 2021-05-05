<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class Category extends ClosureTreeRepository
{

    public function getTree($node = null)
    {
        return  $this->childrenHierarchy(
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

    public function getFormFillArray(): array
    {
        return $this->_build($this->getTree());
    }

    private function _build($tree, $level = 1): array
    {

        $ret = [];

        foreach ($tree as $items) {
            $ret[$items['id']] = str_repeat("&nbsp;", ($level-1)*3). $items['title'];
            if (isset($items['__children'])) {
                $ret += $this->_build($items['__children'], $items['level'] + 1);
            }
        }
        return $ret;
    }

}