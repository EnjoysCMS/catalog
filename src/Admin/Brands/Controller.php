<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Brands;


use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;

/**
 * TODO
 */
#[Route('admin/catalog/brand', '@catalog_brand_')]
final class Controller extends AdminController
{
    #[Route(
        path: 's',
        name: 'list',
        comment: 'Список брендов'
    )]
    public function list(): ResponseInterface
    {

        return $this->response('');
    }


}
