<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Sitemap;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Sitemap\SitemapCollectorInterface;
use EnjoysCMS\Module\Sitemap\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapCollector implements SitemapCollectorInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $repositoryCategory;
    private ObjectRepository|EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $repositoryProduct;

    public function __construct(private EntityManager $em, private UrlGeneratorInterface $urlGenerator)
    {
        $this->repositoryCategory = $this->em->getRepository(Category::class);
        $this->repositoryProduct = $this->em->getRepository(Product::class);
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->urlGenerator->getContext()->setBaseUrl($baseUrl);
    }

    private function checkOffStatusParentCategory(?Category $category, $status = []): bool
    {
        if ($category === null) {
            return in_array(false, $status, true);
        }
        $status[] = $category->isStatus();
        return $this->checkOffStatusParentCategory($category->getParent(), $status);
    }


    public function make()
    {
        /** @var Category $item */
        foreach ($this->repositoryCategory->findBy(['status' => true]) as $item) {
            if ($this->checkOffStatusParentCategory($item)) {
                continue;
            }

            yield new Url(
                $this->urlGenerator->generate(
                    'catalog/category',
                    ['slug' => $item->getSlug()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }

        /** @var Product $product */
        foreach ($this->repositoryProduct->findBy(['active' => true]) as $product) {
            if ($this->checkOffStatusParentCategory($product->getCategory())) {
                continue;
            }

            yield new Url(
                $this->urlGenerator->generate(
                    'catalog/product',
                    ['slug' => $product->getSlug()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }
    }
}
