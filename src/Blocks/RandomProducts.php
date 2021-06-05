<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use DoctrineExtensions\Query\Mysql\Rand;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class RandomProducts extends AbstractBlock
{
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;

    private Environment $twig;
    private string $templatePath;


    public function __construct(ContainerInterface $container, Entity $block)
    {
        parent::__construct($container, $block);

        $em = $this->container->get(EntityManager::class);
        $em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);

        $this->repository = $em->getRepository(Product::class);
        $this->twig = $this->container->get(Environment::class);
        $this->templatePath = $this->getOption('template');
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
        $dbl = $this->repository->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
            ->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true')
            ->where('c.status = true')
            ->orderBy('RAND()')
            ->getQuery()
            ->setMaxResults((int)$this->getOption('limit', 3))
        ;

        return $this->twig->render(
            $this->templatePath,
            [
                'products' => $dbl->getResult(),
                'blockOptions' => $this->getOptions()
            ]
        );
    }


}