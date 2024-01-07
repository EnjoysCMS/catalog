<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use DI\Container;
use Enjoys\Functions\TwigExtension\ConvertSize;
use EnjoysCMS\Module\Catalog\Models\ProductModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: 'catalog/{slug}.html',
    name: 'catalog/product',
    requirements: ['slug' => '[^.]+'],
    options: ['comment' => '[public] Просмотр продуктов (товаров)']
)]
final class Product extends PublicController
{

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */

    public function __invoke(Container $container, ProductModel $productModel): ResponseInterface
    {
        $template_path = '@m/catalog/product.twig';

        $this->twig->addExtension($container->get(ConvertSize::class));

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/product.twig';
        }

//        $matrix = $productModel->getProductEntity()->getGroup()?->getOptionsMatrix();
//        dd($productModel->getProductEntity()->getGroup()?->getDefaultOptionsByProduct($productModel->getProductEntity()));
//        /** @var \EnjoysCMS\Module\Catalog\Repository\Product $repo */
//        $repo = $container->get(EntityManagerInterface::class)->getRepository(\EnjoysCMS\Module\Catalog\Entity\Product::class);
//        $criteria = new Criteria();
//        $criteria->where(Criteria::expr()->eq('p.group', 'dac2fe6f-637f-46ee-b57c-1583afce1776'));
//        dd($repo->findOneByGroupAndOptions('dac2fe6f-637f-46ee-b57c-1583afce1776', [142, 133]));
//        dd(iterator_to_array($productModel->getProductEntity()->getGroup()?->getProductsWithOptions()->getIterator()));
//        foreach ($productModel->getProductEntity()->getGroup()?->getProductsWithOptions() ?? [] as $i=>$optionsValue) {
//            foreach ($optionsValue as $key => $item) {
//                dd($i, $key, $item);
//            }
//        }
//        dd($productModel->getProductEntity()->getGroup()?->getOptionsValues()->count);
        return $this->responseText(
            $this->twig->render(
                $template_path,
                $productModel->getContext(),
            )
        );
    }
}
