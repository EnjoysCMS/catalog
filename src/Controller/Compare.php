<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller;

use Doctrine\ORM\EntityManager;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Exception;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Service\Compare\GoodsComparator;
use EnjoysCMS\Module\Catalog\Service\Compare\Result\LineMatrix;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class Compare extends AbstractController
{

    /**
     * @throws Exception
     */
    #[Route('catalog/compare/add', 'catalog_compare_add', priority: 3)]
    public function add(Cookie $cookie): ResponseInterface
    {
        $parsedBody = json_decode($this->request->getBody()->getContents());
        $productId = $parsedBody->productId ?? null;

        $cookieValueComparisonGoods = json_decode($cookie->get('comparison_goods_ids') ?? '[]', true);

        if ($productId !== null) {
            $cookie->set(
                'comparison_goods_ids',
                json_encode(
                    array_merge($cookieValueComparisonGoods, [$productId])
                )
            );
        }

        return $this->response;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('catalog/compare', 'catalog_compare', priority: 3)]
    public function compare(GoodsComparator $goodsComparator, Config $config): ResponseInterface
    {

        $this->breadcrumbs->add('catalog/index', 'Каталог')->add(title: 'Сравнение товаров');

        /** @var \EnjoysCMS\Module\Catalog\Repository\Product $repo */
        $repo = $this->container->get(EntityManager::class)->getRepository(
            \EnjoysCMS\Module\Catalog\Entity\Product::class
        );


        $productIds = json_decode($this->request->getCookieParams()['comparison_goods_ids'] ?? '[]', true);

        /** @var \EnjoysCMS\Module\Catalog\Entity\Product[] $products */
        $products = $repo->findByIds($productIds);

        $goodsComparator->addProducts($products);


        return $this->response($this->twig->render('@m/catalog/compare.twig', [
            'breadcrumbs' => $this->breadcrumbs,
            'comparator' => $goodsComparator,
            'comparisonGoods' => (new LineMatrix($goodsComparator))->setRemoveRepeat(
                (bool)$this->request->getQueryParams()['remove_repeat_values'] ?? false
            ),
            'config' => $config
        ]));
    }
}
