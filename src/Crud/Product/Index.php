<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index implements ModelInterface
{

    private Repositories\Product|EntityRepository $productRepository;

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    /**
     * @throws NotFoundException
     */
    public function getContext(): array
    {
        $pagination = new Pagination($this->request->getQueryParams()['page'] ?? 1, 10);
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
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                'Список товаров (продуктов)',
            ],
        ];
    }
}
