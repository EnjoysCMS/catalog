<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;

final class Index implements ModelInterface
{

    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;

    public function __construct(private EntityManager $em, private ServerRequestWrapper $requestWrapper)
    {
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    public function getContext(): array
    {
        $pagination = new Pagination($this->requestWrapper->getQueryData('page', 1), 10);
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
