<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Files;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage
{
    private EntityRepository|ProductRepository $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
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
            'config' => $this->config,
            'subtitle' => 'Управление файлами',
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер файлов: %s', $this->product->getName()),
            ],
        ];
    }


}
