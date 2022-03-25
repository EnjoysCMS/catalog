<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Module\Catalog\Models\ProductModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: 'catalog/{slug}.html',
    name: 'catalog/product',
    requirements: ['slug' => '[^.]+'],
    options: ['aclComment' => '[public] Просмотр продуктов (товаров)']
)]
final class Product extends PublicController
{

    /**
     * @throws SyntaxError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws DependencyException
     * @throws LoaderError
     */

    public function __invoke(ContainerInterface $container): ResponseInterface
    {
        $template_path = '@m/catalog/product.twig';

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
