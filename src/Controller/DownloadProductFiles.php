<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: "catalog/download/file/{filepath}",
    name: "@a/catalog/download-file",
    requirements: [
        'filepath' => '.+'
    ],
    options: [
        "aclComment" => "[PUBLIC] Скачивание файлов"
    ]
)]
final class DownloadProductFiles extends BaseController
{
    public function __invoke(EntityManagerInterface $em, ServerRequestWrapper $request): ResponseInterface
    {
        $file = $em->getRepository(ProductFiles::class)->findOneBy([
            'filename' => $request->getAttributesData('filepath')
        ]);

        if ($file === null) {
            throw new NoResultException();
        }
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
            ->withAddedHeader('Content-Length', $file->getFilesize())
        ;
        $response->getBody()->write(
            file_get_contents(
                $_ENV['UPLOAD_DIR'] . '/catalog_files/' . $file->getFilename()
            )
        );
        return $response;
    }
}
