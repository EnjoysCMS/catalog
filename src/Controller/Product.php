<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequest;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class Product
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;
    private ServerRequestInterface $serverRequest;
    private Environment $twig;

    public function __construct(ServerRequestInterface $serverRequest, EntityManager $entityManager, Environment $twig)
    {
        $this->repository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class);

        $this->serverRequest = $serverRequest;
        $this->twig = $twig;
    }

    /**
     * @Route(
     *     name="catalog/product",
     *     path="catalog/{slug}.html",
     *     requirements={"slug": "[^.]+"},
     *     options={
     *      "aclComment": "[public] Просмотр продуктов (товаров)"
     *     }
     * )
     */
    public function view()
    {
        $slug = array_reverse(explode('/', $this->serverRequest->get('slug')));
        $product = $this->repository->findBySlug($slug);

        if($product === null){
            Error::code(404);
        }

        $template_path = '@m/catalog/product.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path =  __DIR__ . '/../../template/product.twig.sample';
        }

        return $this->twig->render(
            $template_path,
            [
                'product' => $product
            ]
        );
    }

}