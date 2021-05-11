<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequest;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class Category
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;
    private ServerRequestInterface $serverRequest;
    private Environment $twig;
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $productRepository;

    public function __construct(ServerRequestInterface $serverRequest, EntityManager $entityManager, Environment $twig)
    {
        $this->categoryRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);
        $this->productRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class);
        $this->serverRequest = $serverRequest;
        $this->twig = $twig;
    }

    /**
     * @Route(
     *     name="catalog/category",
     *     path="catalog/{slug}",
     *     requirements={"slug": "[^.]+"},
     *     options={
     *      "aclComment": "[public] Просмотр категорий"
     *     }
     * )
     */
    public function view()
    {
        $category = $this->categoryRepository->findByPath($this->serverRequest->get('slug'));
        if ($category === null) {
            Error::code(404);
        }

        $products = $this->productRepository->findByCategory($category);

        $template_path = '@m/catalog/category.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path =  __DIR__ . '/../../template/category.twig.sample';
        }

        return $this->twig->render(
            $template_path,
            [
                'category' => $category,
                'products' => $products,
            ]
        );
    }

}