<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Functions\TwigExtension\ConvertSize;
use EnjoysCMS\Module\Catalog\Models\ProductModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
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
     */

    public function __invoke(ContainerInterface $container): ResponseInterface
    {
        $template_path = '@m/catalog/product.twig';

        $this->twig->addExtension($container->get(ConvertSize::class));

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/product.twig';
        }


        return $this->responseText(
            $this->twig->render(
                $template_path,
                $container->make(ProductModel::class)->getContext(),
            )
        );
    }
}
