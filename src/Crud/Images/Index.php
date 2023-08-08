<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index implements ModelInterface
{

    private Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        ServerRequestInterface $request,
    ) {
        $this->product = $entityManager->getRepository(Product::class)->find(
            $request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();
    }

    public function getContext(): array
    {
        return [
            'product' => $this->product,
            'images' => $this->entityManager->getRepository(Image::class)->findBy(['product' => $this->product]),
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер изображений: `%s`', $this->product->getName()),
            ],
        ];
    }
}
