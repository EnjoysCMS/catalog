<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Module\Catalog\Entities\Product;

final class Index implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $productRepository;

    public function __construct(EntityManager $entityManager, ServerRequestInterface $serverRequest)
    {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->productRepository = $entityManager->getRepository(Product::class);
    }

    public function getContext(): array
    {
        $pagination = new Pagination($this->serverRequest->get('page', 1), 10);
        $qb = $this->productRepository->getFindAllQuery();
        $qb
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimitItems())
        ;
        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());
        return [
            'products' => $result,
            'pagination' => $pagination,
        ];
    }
}