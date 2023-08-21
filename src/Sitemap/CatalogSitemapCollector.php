<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Sitemap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\Persistence\Mapping\MappingException;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Sitemap\SitemapCollectorInterface;
use EnjoysCMS\Module\Sitemap\Url;
use Exception;
use Generator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CatalogSitemapCollector implements SitemapCollectorInterface
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Category $repositoryCategory;
    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Product $repositoryProduct;

    /**
     * @throws NotSupported
     */
    public function __construct(private EntityManager $em, private UrlGeneratorInterface $urlGenerator)
    {
        $this->repositoryCategory = $this->em->getRepository(Category::class);
        $this->repositoryProduct = $this->em->getRepository(Product::class);
    }

    public function setBaseUrl(string $baseUrl): void
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


    /**
     * @throws MappingException
     * @throws Exception
     */
    public function make(): Generator
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

        $allCount = $this->repositoryProduct->count(['active' => true]);
        $limit = 100;
        $offset = 0;

        while ($allCount > $offset) {
            /** @var Product $product */
            foreach ($this->repositoryProduct->findBy(['active' => true], limit: $limit, offset: $offset) as $product) {
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
            $this->em->clear();
            $offset += $limit;
        }
    }
}
