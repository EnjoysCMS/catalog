<?php

namespace EnjoysCMS\Module\Catalog\Crud\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use EnjoysCMS\Module\Catalog\Entities\Category;

class SaveCategoryStructure
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;

    public function __construct(private EntityManager $em)
    {
        $this->categoryRepository = $this->em->getRepository(Category::class);

    }

    /**
     * @throws ORMException
     */
    public function __invoke($data, Category|null $parent = null): void
    {
        $this->doAction($data, $parent);
    }

    /**
     * @throws ORMException
     */
    private function doAction($data, Category|null $parent = null): void
    {
        foreach ($data as $key => $value) {
            /** @var Category $item */
            $item = $this->categoryRepository->find($value->id);
            $item->setParent($parent);
            $item->setSort($key);
            $this->em->persist($item);
            if (isset($value->children)) {
                $this->doAction($value->children, $item);
            }
        }
    }
}
