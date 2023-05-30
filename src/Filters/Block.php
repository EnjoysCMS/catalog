<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Filters\Entity\FilterEntity;
use EnjoysCMS\Module\Catalog\Repositories\FilterRepository;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Block extends AbstractBlock
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
        $request = $this->container->get(ServerRequestInterface::class);
        $em = $this->container->get(EntityManager::class);
        $filterFactory = $this->container->get(FilterFactory::class);
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $em->getRepository(Category::class);
        /** @var Category $category */
        $category = $categoryRepository->findByPath($request->getAttribute('slug', ''));

        /** @var EntityRepository|FilterRepository $filterRepository */
        $filterRepository = $em->getRepository(FilterEntity::class);

        /** @var FilterEntity[] $allowedFilters */
        $allowedFilters = $filterRepository->findBy(['category' => $category], ['order' => 'asc']);

        /** @var int[] $pids id всех продуктов в этой категории */
        $pids = array_column(
            $em->createQueryBuilder()
                ->select('p.id')
                ->from(Product::class, 'p')
                ->leftJoin('p.category', 'c')
                ->where('p.category IN (:category)')
                ->distinct()
                ->setParameter('category', $categoryRepository->getAllIds($category))
                ->getQuery()
                ->getArrayResult(),
            'id'
        );

//        $filters = new \SplObjectStorage();
//        foreach ($allowedFilters as $filter) {
//            $filters[$filter] = $filterRepository->getValues($filter->getOptionKey(), $pids);
//        }

        $form = new Form('get');
        $hasFilters = false;
        foreach ($allowedFilters as $filterMetaData) {
            /** @var FilterInterface $filter */
            $filter = $filterFactory->create($filterMetaData->getFilterType(), $filterMetaData->getParams());
            $values = $filter->getPossibleValues($pids);
            if ($values === []) {
                continue;
            }

            $form->setDefaults(
                array_merge_recursive(
                    $form->getDefaultsHandler()->getDefaults(),
                    $filter->getFormDefaults($values)
                )
            );

            $filter->addFormElement($form, $values);
            $hasFilters = true;
        }


        $form->submit('submit1', 'Показать')->removeAttribute('name');


        $renderForm = $this->container->get(RendererInterface::class);
        $renderForm->setForm($form);

        return $this->twig->render(
            empty(
            $this->getOption(
                'template'
            )
            ) ? '../modules/catalog/template/blocks/filter_v2.twig' : $this->getOption(
                'template'
            ),
            [
//                'filters' => $filters,
                'form' => $hasFilters ? $renderForm->output() : null,
                'blockOptions' => $this->getOptions()
            ]
        );
    }
}
