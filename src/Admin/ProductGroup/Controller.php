<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\ProductGroup;


use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;

/**
 * TODO
 */
#[Route('admin/catalog/product_group', '@catalog_product_group_')]
final class Controller extends AdminController
{
    #[Route(
        path: 's',
        name: 'list',
        comment: 'Просмотр групп товаров'
    )]
    public function list(): ResponseInterface
    {
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
