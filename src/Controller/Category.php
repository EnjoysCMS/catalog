<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use App\Helper\Pagination;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequest;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Category
{

    /**
     * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;
    private ServerRequestInterface $serverRequest;
    private Environment $twig;
    /**
     * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $productRepository;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->categoryRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);
        $this->productRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class);
        $this->serverRequest = $serverRequest;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
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
     * @throws \Exception
     */
    public function view(ContainerInterface $container): string
    {
        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $category */
        $category = $this->categoryRepository->findByPath($this->serverRequest->get('slug'));
        if ($category === null) {
            Error::code(404);
        }

        $breadcrumbs = $container->get(BreadcrumbsInterface::class);
        $breadcrumbs->add($this->urlGenerator->generate('catalog/index'), 'Каталог');
        foreach ($category->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbs->add(
                $this->urlGenerator->generate('catalog/category', ['slug' => $breadcrumb['slug']]),
                $breadcrumb['title']
            );
        }

//        $products = $this->productRepository->findByCategory($category);
        $pagination = new Pagination($this->serverRequest->get('page', 1), 12);

        $qb = $this->productRepository
            ->getQueryFindByCategory($category)
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimitItems())
        ;
        $result = new Paginator($qb);


        $pagination->setTotalItems($result->count());

        $template_path = '@m/catalog/category.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/category.twig.sample';
        }

        return $this->twig->render(
            $template_path,
            [
                'category' => $category,
                'pagination' => $pagination,
                'products' => $result,
                'breadcrumbs' => $breadcrumbs->get(),
            ]
        );
    }

}