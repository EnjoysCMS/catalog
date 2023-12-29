<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\ProductGroup;


use DI\Container;
use Doctrine\ORM\EntityManagerInterface;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\ProductGroup;
use Psr\Http\Message\ResponseInterface;

/**
 * TODO
 */
#[Route('admin/catalog/product_group', '@catalog_product_group_')]
final class Controller extends AdminController
{
    public function __construct(
        Container $container,
        Config $config,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct($container, $config, $adminConfig);
    }

    #[Route(
        path: 's',
        name: 'list',
        comment: 'Просмотр групп товаров'
    )]
    public function list(): ResponseInterface
    {
        $repository = $this->em->getRepository(ProductGroup::class);
        dd($repository->find('389a33b7-b0b0-4faa-b7b6-c0b634fe38c8')->getProducts()->toArray());
        return $this->response('');
    }

    #[Route(
        path: '/add',
        name: 'add',
        comment: 'Добавить новую группу товаров (объединение карточек)'
    )]
    public function add(): ResponseInterface
    {
        /**
         * Наименование группы
         * Выбор характеристик товара, различные для вариантов
         * Добавление товаров, если характеристик нет, не добавлять
         */
        return $this->response('');
    }
}
