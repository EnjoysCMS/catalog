<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductModel implements ModelInterface
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    private Product $product;

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private BreadcrumbsInterface $breadcrumbs,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
    }

    public function getContext(): array
    {
        if($this->product->getUrl() !== $this->product->getCurrentUrl()){
            Redirect::http($this->urlGenerator->generate('catalog/product', ['slug' => $this->product->getSlug()]), 301);
        }

        return [
            '_title' => sprintf(
                '%2$s - %3$s - %1$s',
                Setting::get('sitename'),
                $this->product->getName(),
                $this->product->getCategory()?->getFullTitle(reverse: true) ?? 'Каталог'
            ),
            'product' => $this->product,
            'optionKeyRepository' => $this->em->getRepository(OptionKey::class),
            'optionValueRepository' => $this->em->getRepository(OptionValue::class),
            'priceGroups' => $this->em->getRepository(PriceGroup::class)->findAll(),
            'breadcrumbs' => $this->getBreadcrumbs()
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->findBySlug($this->serverRequest->get('slug'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    private function getBreadcrumbs(): array
    {
        $this->breadcrumbs->add($this->urlGenerator->generate('catalog/index'), 'Каталог');
        $breadcrumbs = $this->product->getCategory()?->getBreadcrumbs();
        foreach ((array)$breadcrumbs as $breadcrumb) {
            $this->breadcrumbs->add(
                $this->urlGenerator->generate('catalog/category', ['slug' => $breadcrumb['slug']]),
                $breadcrumb['title']
            );
        }
        $this->breadcrumbs->add(null, $this->product->getName());
        return $this->breadcrumbs->get();
    }
}
