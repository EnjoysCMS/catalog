<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class Index
{
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;
    private Environment $twig;


    public function __construct(EntityManager $entityManager, Environment $twig)
    {
        $this->categoryRepository = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);

        $this->twig = $twig;
    }

    /**
     * @Route(
     *     name="catalog/index",
     *     path="catalog",
     *     options={
     *      "aclComment": "[public] Просмотр категорий (индекс)"
     *     }
     * )
     */
    public function view(ContainerInterface $container)
    {

        $categories = $this->categoryRepository->getRootNodes();


        $template_path = '@m/catalog/category_index.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path =  __DIR__ . '/../../template/category_index.twig.sample';
        }

        $breadcrumbs = $container->get(BreadcrumbsInterface::class);
        $breadcrumbs->add(null, 'Каталог');

        return $this->twig->render(
            $template_path,
            [
                'categories' => $categories,
                'breadcrumbs' => $breadcrumbs->get(),
            ]
        );
    }
}