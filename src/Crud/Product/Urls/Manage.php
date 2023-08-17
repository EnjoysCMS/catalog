<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage
{
    private EntityRepository|ProductRepository $productRepository;
    protected Product $product;


    /**
     * @throws NotSupported
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }

    public function getContext(): array
    {
        return [
            'product' => $this->product,
            'subtitle' => 'URLs',
            'breadcrumbs' => [
                $this->urlGenerator->generate('@catalog_admin') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер ссылок: %s', $this->product->getName()),
            ],
        ];
    }
}
