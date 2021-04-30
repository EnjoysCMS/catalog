<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Catalog\Entities\Category;

final class Index implements ModelInterface
{
    private EntityManager $em;
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository|ObjectRepository
     */
    private $categoryRepository;


    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->categoryRepository = $this->em->getRepository(Category::class);
    }

    public function getContext(): array
    {
        return [
            'categories' => $this->categoryRepository->getRootNodes()
        ];
    }
}