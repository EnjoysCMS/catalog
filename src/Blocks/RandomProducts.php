<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use DoctrineExtensions\Query\Mysql\Rand;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class RandomProducts
{
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;

    private Environment $twig;
    private ?string $templatePath;


    /**
     * @param ContainerInterface&FactoryInterface $container
     * @param Entity $block
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(private  ContainerInterface $container, Entity $block)
    {

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
        $qb = $this->repository->createQueryBuilder('p')
            ->select('p', 'c', 't', 'i')
            ->leftJoin('p.category', 'c')
            ->leftJoin('c.parent', 't')
        ;


        if ($this->getOption('only_with_images') === null) {
            $qb = $qb->leftJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true');
        } else {
            $qb = $qb->innerJoin('p.images', 'i', Join::WITH, 'i.product = p.id AND i.general = true');
        }


        $qb = $qb->where('c.status = true')
            ->orderBy('RAND()')
            ->getQuery()
            ->setMaxResults((int)$this->getOption('limit', 3))
        ;

        return $this->twig->render(
            (string)$this->templatePath,
            [
                'products' => $qb->getResult(),
                'blockOptions' => $this->getOptions()
            ]
        );
    }


}
