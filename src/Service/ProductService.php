<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Pagination\Pagination;
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
     * @param array $criteria
     * @param string|null $search
     * @param array $orders
     * @return array{products: Product[], pagination: Pagination}
     * @throws NotFoundException
     * @throws QueryException
     */
    public function getProducts(
        int $page = 1,
        int $limit = 10,
        array $criteria = [],
        array $orders = ['p.id' => 'desc']
    ): array {
        $pagination = new Pagination($page, $limit);
        $qb = $this->productRepository->getFindAllBuilder();

        foreach ($orders as $sort => $dir) {
            $qb->orderBy($sort, $dir);
        }

        foreach ($criteria as $field => $value) {
            if ($value instanceof Criteria) {
                $qb->addCriteria($value);
                continue;
            }
            $qb->addCriteria(Criteria::create()->where(Criteria::expr()->eq($field, $value)));
        }

        $query = $qb->getQuery()
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
