<?php

namespace EnjoysCMS\Module\Catalog\Admin\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use LogicException;

class SaveCategoryStructure
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(private readonly EntityManager $em)
    {
        $this->categoryRepository = $this->em->getRepository(Category::class);

    }

    /**
     * @throws ORMException
     */
    public function __invoke(iterable $data, Category|null $parent = null): void
    {
        $this->doAction($data, $parent);
    }

    /**
     * @throws ORMException
     */
    private function doAction(iterable $data, Category|null $parent = null, array $tmp = []): void
    {
        foreach ($data as $key => $value) {
            /** @var Category $item */
            $item = $this->categoryRepository->find($value->id);

            $item->setParent($parent);
            $item->setSort($key);
            $this->em->persist($item);

            if (array_key_exists($slug = $item->getSlug(), $tmp)){
                throw new LogicException(sprintf('Пути совпадают в: %s', $slug));
            }
            $tmp[$slug] = true;

            if (isset($value->children)) {
                $this->doAction($value->children, $item, $tmp);
            }
        }
    }
}
