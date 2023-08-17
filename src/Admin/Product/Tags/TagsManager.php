<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Tags;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Module\Catalog\Entities\ProductTag;

final class TagsManager
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getTagsFromArray(array $tags = []): array
    {
        $ret = [];
        foreach ($tags as $tag) {
            if ($tag instanceof ProductTag) {
                $ret[] = $tag;
                continue;
            }

            if (empty($tag)) {
                continue;
            }

            $tagEntity = $this->entityManager->getRepository(ProductTag::class)->findOneBy(['name' => $tag]);

            if ($tagEntity === null) {
                $tagEntity = new ProductTag();
                $tagEntity->setName($tag);
                $this->entityManager->persist($tagEntity);
            }
            $ret[] = $tagEntity;
        }
        $this->entityManager->flush();

        return $ret;
    }


}
