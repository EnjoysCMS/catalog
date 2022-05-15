<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\BaseController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: "catalog/download/file",
    name: "@a/catalog/download-file",
    options: [
        "aclComment" => "[PUBLIC] Скачивание файлов"
    ]
)]
final class DownloadProductFiles extends BaseController
{
    public function __invoke()
    {
        // TODO: Implement __invoke() method.
    }
}
