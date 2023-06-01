<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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

    public function __construct(
        private Environment $twig,
        private ServerRequestInterface $request,
        private EntityManager $em,
        private FilterFactory $filterFactory,
        private RendererInterface $renderForm,
        Entity $block
    ) {
        parent::__construct($block);
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @throws LoaderError
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotSupported
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function view(): string
    {
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $this->em->getRepository(Category::class);
        /** @var Category $category */
        $category = $categoryRepository->findByPath($this->request->getAttribute('slug', ''));

        /** @var EntityRepository|FilterRepository $filterRepository */
        $filterRepository = $this->em->getRepository(FilterEntity::class);

        /** @var FilterEntity[] $allowedFilters */
        $allowedFilters = $filterRepository->findBy(['category' => $category], ['order' => 'asc']);

        /** @var int[] $productIds id всех продуктов в этой категории */
        $productIds = array_column(
            $this->em->createQueryBuilder()
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


        $form = new Form('get');
        $hasFilters = false;

        foreach ($allowedFilters as $filterMetaData) {

            $filter = $this->filterFactory->create($filterMetaData->getFilterType(), $filterMetaData->getParams());

            if ($filter === null){
                continue;
            }

            $values = $filter->getPossibleValues($productIds);
            if ($values === []) {
                continue;
            }
            $form = $filter->getFormElement($form, $values);
            $hasFilters = true;
        }

        $form->submit('submit1', 'Показать')->removeAttribute('name');


        $this->renderForm->setForm($form);

        $template = empty($this->getOption('template'))
            ? '../modules/catalog/template/blocks/filter.twig' : $this->getOption('template');

        return $this->twig->render(
            $template,
            [
//                'filters' => $filters,
                'form' => $hasFilters ? $this->renderForm->output() : null,
                'blockOptions' => $this->getOptions()
            ]
        );
    }
}
