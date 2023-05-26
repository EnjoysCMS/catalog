<?php

namespace EnjoysCMS\Module\Catalog\Blocks;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\FilterRepository;
use EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Filter extends AbstractBlock
{

    private Environment $twig;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(private Container $container, Entity $block)
    {
        parent::__construct($block);
        $this->twig = $this->container->get(Environment::class);
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function view(): string
    {
        $em = $this->container->get(EntityManager::class);
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $em->getRepository(Category::class);
        /** @var EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository */
        $productRepository = $em->getRepository(Product::class);
        /** @var EntityRepository|FilterRepository $filterRepository */
        $filterRepository = $em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Filter::class);
        $request = $this->container->get(ServerRequestInterface::class);
        $slug = $request->getAttribute('slug', '');
        $category = $categoryRepository->findByPath($slug);
//        $filters = $filterRepository->findBy(['category' => $category]);
//
//        /** @var int[] $pids id всех продуктов в этой категории */
//        $pids = array_column(
//            $productRepository->getFindAllBuilder()
//                ->select('p.id')
//                ->where('c.id = :category')
//                ->setParameter('category', $category)
//                ->distinct()
//                ->getQuery()->getArrayResult(),
//            'id'
//        );
//
//
//        $values = $em->getRepository(OptionValue::class)->createQueryBuilder('v')
//            ->leftJoin(OptionKey::class, 'k', Expr\Join::WITH, 'k.id = v.optionKey')
//            ->leftJoin(\EnjoysCMS\Module\Catalog\Entities\Filter::class, 'f', Expr\Join::WITH, 'f.optionKey = k.id')
//            ->where('f.category = :category')
//            ->andWhere(':pids MEMBER OF v.products')
//            ->setParameters([
//                'pids' => $pids,
//                'category' => $category
//            ])
//            ->getQuery()
//            ->getResult();

        $filters = $filterRepository->getCategoryFilters($category);
        dump($filters);

        return $this->twig->render(
            empty($this->getOption('template')) ? '../modules/catalog/template/blocks/filter.twig' : $this->getOption(
                'template'
            ),
            [
                'filters' => $filters,
                'blockOptions' => $this->getOptions()
            ]
        );
    }
}
