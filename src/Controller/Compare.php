<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Service\Compare\ComparisonGoods;
use EnjoysCMS\Module\Catalog\Service\Compare\Result\LineMatrix;
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
    public function compare(ComparisonGoods $comparisonGoods): ResponseInterface
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
            'fcce79f9-c326-4b58-96e7-a69fc3db287c',
            'cb24a71a-c862-4fbd-a2e9-9b23e8fc016b',
//            '9973d0a0-9f4e-4f3e-970d-322aae9c01f0',
//            'ff080240-10d9-41a2-9f0a-d98b92405f64',
//            'fe7ac4c5-b253-43cd-b2a7-f7fe1a9f0b13',
//            '14e87f18-dd49-4cf0-ab32-1b47c53e5818',
//            '1cc693dc-6d94-431f-ba93-c3647b74abb9',
//            '2187bc19-b8ce-4c93-8b05-e99e65c08a30',
//            '242a89f5-dcb0-49f1-9240-ae0622d2232c',
//            '2a608c39-3fe0-4d15-ae1b-c50ad62c03b7',
//            '331ce48b-52b6-421d-bcf7-274be4b01c99',
        ]);

        $comparisonGoods->addProducts($products);

        $lineMatrix = new LineMatrix($comparisonGoods);

//        dd($lineMatrix->getData());

        return $this->response($this->twig->render('@m/catalog/compare.twig', [
            'breadcrumbs' => $this->breadcrumbs,
            'comparisonGoods' => $comparisonGoods,
            'lineMatrix' => $lineMatrix,
        ]));
    }
}
