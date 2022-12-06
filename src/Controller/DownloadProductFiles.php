<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: "catalog/download/file/{filepath}",
    name: "@a/catalog/download-file",
    requirements: [
        'filepath' => '.+'
    ],
    options: [
        "comment" => "[PUBLIC] Скачивание файлов"
    ]
)]
final class DownloadProductFiles extends BaseController
{
    public function __invoke(
        EntityManagerInterface $em,
        ServerRequestInterface $request,
        Config $config
    ): ResponseInterface {
        $file = $em->getRepository(ProductFiles::class)->findOneBy([
            'filePath' => $request->getAttribute('filepath')
        ]);

        if ($file === null) {
            throw new NoResultException();
        }

        /** @var ProductFiles $file */
        $filesystem = $config->getFileStorageUpload($file->getStorage())->getFileSystem();

        /** @var ProductFiles $file */
        $file->setDownloads($file->getDownloads() + 1);
        $em->flush();

        $response = $this->response
            ->withAddedHeader('Content-Description', 'File Transfer')
            ->withAddedHeader('Content-Disposition', sprintf('attachment; filename="%s"', $file->getFilename()))
            ->withAddedHeader('Content-Transfer-Encoding', 'binary')
            ->withAddedHeader('Expires', 0)
            ->withAddedHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withAddedHeader('Pragma', 'public')
            ->withAddedHeader('Content-Length', $file->getFileSize())
        ;
        $response->getBody()->write($filesystem->read($file->getFilePath()));
        return $response;
    }
}
