<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;

final class ProductService
{

    private Repositories\Product|EntityRepository $productRepository;

    public function __construct(
        private EntityManager $em
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }


    /**
     * @param int $page
     * @param int $limit
     * @return array{products: Product[], pagination: Pagination}
     * @throws NotFoundException
     */
    public function getProducts(int $page = 1, int $limit = 10): array
    {
        $pagination = new Pagination($page, $limit);
        $qb = $this->productRepository->getFindAllBuilder();
        $query = $qb->orderBy('p.id', 'desc')
            ->getQuery()
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimitItems());

        $result = new Paginator($query);
        $pagination->setTotalItems($result->count());
        return [
            'products' => $result,
            'pagination' => $pagination,
        ];
    }
}
