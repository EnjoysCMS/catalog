<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Blocks as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Container\ContainerInterface;
use Twig\Environment;


final class RandomProducts extends AbstractBlock
{
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;
    /**
     * @var Environment
     */
    private $twig;

    private ServerRequestInterface $serverRequest;

    public function __construct(ContainerInterface $container, Entity $block)
    {
        parent::__construct($container, $block);
        $this->repository = $this->container->get(EntityManager::class)->getRepository(Product::class);
        $this->twig = $this->container->get(Environment::class);
        $this->serverRequest = $this->container->get(ServerRequestInterface::class);
        $this->templatePath = $this->getOption('template');
    }


    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    public function view()
    {
        $dbl = $this->repository->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
            ->where('c.status = true')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $this->getRandIds())
            ->getQuery()
        ;

        return $this->twig->render(
            $this->templatePath,
            [
                'products' => $dbl->getResult(),
                'blockOptions' => $this->getOptions()
            ]
        );
    }

    private function getRandIds()
    {
        $ids = $this->repository->createQueryBuilder('p')
            ->select('p.id')
            ->leftJoin('p.category', 'c')
            ->where('c.status = true')
            ->getQuery()
            ->getResult('Column')
        ;

        $limit = (int)$this->getOption('limit', 3);
        $count = count($ids);

        return array_rand(array_flip($ids), ($limit > $count) ? $count : $limit);
    }

}