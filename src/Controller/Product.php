<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use App\Components\Breadcrumbs;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequest;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Assets;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Module\Catalog\Entities\Image;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Product
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;
    private ServerRequestInterface $serverRequest;
    private Environment $twig;
    private EntityManager $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class);
        $this->serverRequest = $serverRequest;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
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
    public function view(ContainerInterface $container)
    {
        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
        $product = $this->repository->findBySlug($this->serverRequest->get('slug'));
        if ($product === null) {
            Error::code(404);
        }

        $breadcrumbs = new Breadcrumbs($container);
        $breadcrumbs->add($this->urlGenerator->generate('catalog/index'), 'Каталог');
        foreach ($product->getCategory()->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbs->add(
                $this->urlGenerator->generate('catalog/category', ['slug' => $breadcrumb['slug']]),
                $breadcrumb['title']
            );
        }
        $breadcrumbs->add(null, $product->getName());


        $template_path = '@m/catalog/product.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/product.twig.sample';
        }

        Assets::css(
            [
                'template/modules/catalog/assets/style.css',
                'template/modules/catalog/assets/magnific-popup.css',
            ]
        );


        Assets::js(
            [
                'template/modules/catalog/assets/jquery.magnific-popup.min.js',
            ]
        );

        return $this->twig->render(
            $template_path,
            [
                'product' => $product,
                'breadcrumbs' => $breadcrumbs->get(),
            ]
        );
    }

}