<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Service\Compare\CompareGoods;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class Compare extends AbstractController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('catalog/compare', 'catalog_compare', priority: 3)]
    public function compare(CompareGoods $compareGoods): ResponseInterface
    {
        $this->breadcrumbs->add('catalog/index', 'Каталог')->add(title: 'Сравнение товаров');

        /** @var \EnjoysCMS\Module\Catalog\Repository\Product $repo */
        $repo = $this->container->get(EntityManager::class)->getRepository(
            \EnjoysCMS\Module\Catalog\Entity\Product::class
        );
        /** @var \EnjoysCMS\Module\Catalog\Entity\Product[] $products */
        $products = $repo->findByIds([
            '536b9e0c-8ae7-4c57-aecd-6e8c1105b1eb',
            'f5b6cc46-c2fa-42a8-86fe-2930f80ba16f',
            'fcce79f9-c326-4b58-96e7-a69fc3db287c'
        ]);

        $compareGoods->addProducts($products);
//
//        $compare = [];
//        $compareKeys = ['', 'Производитель', 'Артикул', 'Цена'];
//        foreach ($products as $product) {
//            $compare[$product->getId()] = [
//                '' => $product->getName(),
//                'Производитель' => $product->getVendor()->getName(),
//                'Артикул' => $product->getVendorCode(),
//                'Цена' => $product->getPrice('ROZ')->format(),
//            ];
//
//            foreach ($product->getOptions() as $options) {
//                $compareKeys[] = $options['key']->__toString();
//                $compare[$product->getId()][$options['key']->__toString()] = implode(', ', $options['values']);
//            }
//        }
//
//        $compareNull = array_fill_keys(array_unique($compareKeys), null);
//
//        $compare = array_map(function ($item) use ($compareNull) {
//            return array_merge($compareNull, $item);
//        }, $compare);
//
//        dd($compare);

        dd($compareGoods, $compareGoods->getComparedTable());

        return $this->response($this->twig->render('@m/catalog/compare.twig', [
            'breadcrumbs' => $this->breadcrumbs,
            'products' => $products,
            'compare' => $compare
        ]));
    }
}
