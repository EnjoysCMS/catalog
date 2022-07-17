<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use Doctrine\ORM\EntityManager;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index implements ModelInterface
{

    private ?Product $product;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $entityManager,
        ServerRequestWrapper $request,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {
        $this->product = $entityManager->getRepository(Product::class)->find($request->getQueryData('product_id'));
        if ($this->product === null) {
            throw new NotFoundException(
                sprintf('Not found by product_id: %s', $request->getQueryData('product_id'))
            );
        }
    }

    public function getContext(): array
    {
        return [
            'product' => $this->product,
            'config' => $this->config,
            'images' => $this->entityManager->getRepository(Image::class)->findBy(['product' => $this->product]),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер изображений: `%s`', $this->product->getName()),
            ],
        ];
    }
}
