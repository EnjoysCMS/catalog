<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ObjectRepository;
use DoctrineExtensions\Query\Mysql\Rand;
use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Module\Catalog\Entity\Product;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[\EnjoysCMS\Core\Block\Annotation\Block(
    name: 'Продукты (рандомно)',
    options: [
        'template' => [
            'value' => '',
            'name' => 'Путь до template',
            'description' => 'Обязательно',
        ],
        'title' => [
            'value' => '',
            'name' => 'Заголовок блока',
            'description' => 'Для отображения в шаблоне (необязательно)',
        ],
        'limit' => [
            'value' => 3,
            'name' => 'Лимит возвращаемых записей',
            'description' => 'Обязательно',
        ],
        'cacheLimit' => [
            'value' => 0,
            'name' => 'Время кэша в секундах',
            'description' => 'Необязательно',
        ],
        'only_with_images' => [
            'value' => ['withimg'],
            'name' => '&nbsp;',
            'description' => '',
            'form' => [
                'type' => 'checkbox',
                'data' => [
                    'withimg' => 'Показывать товары только с фото'
                ]
            ]
        ],
    ]
)]
final class RandomProducts extends AbstractBlock
{

    private \EnjoysCMS\Module\Catalog\Repository\Product|EntityRepository $repository;


    /**
     * @throws NotSupported
     */
    public function __construct(EntityManager $em, private readonly Environment $twig)
    {
        $em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);
        $this->repository = $em->getRepository(Product::class);
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
            (string)$this->getOption('template'),
            [
                'products' => $qb->getResult(),
                'blockOptions' => $this->getOptions()
            ]
        );
    }


}
