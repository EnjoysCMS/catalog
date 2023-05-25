<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Events\PostLoadAndSaveImage;
use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Download implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;
    private FilesystemOperator $filesystem;
    private ?ThumbnailService $thumbnailService;


    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(private Config $config, private EventDispatcherInterface $dispatcher)
    {
        $this->filesystem = $this->config->getImageStorageUpload()->getFileSystem();
        $this->thumbnailService = $this->config->getThumbnailCreationService();
    }

    public function getTemplatePath(string $templateRootPath): string
    {
        return $templateRootPath . '/form.twig';
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    private function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }


    public function getForm(): Form
    {
        $form = new Form();

        $form->text('image', 'Ссылка на изображение');

        $form->submit('download');
        return $form;
    }


    /**
     * @throws FilesystemException
     * @throws GuzzleException
     */
    public function upload(ServerRequestInterface $request): Generator
    {
        $this->loadAndSave($request->getParsedBody()['image'] ?? null);
        yield $this;
    }

    /**
     * @throws FilesystemException
     * @throws GuzzleException
     */
    public function loadAndSave(string $link): void
    {
        $client = new Client(
            [
                'verify' => false,
                RequestOptions::IDN_CONVERSION => true
            ]
        );
        $response = $client->get($link);
        $data = $response->getBody()->getContents();
        $ext = $this->getExt($response->getHeaderLine('Content-Type'));
        $newFilename = md5((string)microtime(true));
        $subDirectory = $newFilename[0] . '/' . $newFilename[1];
        $this->setName($subDirectory . '/' . $newFilename);
        $this->setExtension($ext);
        $targetPath = $this->getName() . '.' . $this->getExtension();

        $this->filesystem->write($targetPath, $data);
        $this->dispatcher->dispatch(new PostLoadAndSaveImage($targetPath, $this->filesystem));

        $this->thumbnailService?->make($targetPath, $this->filesystem);
    }

    private function getExt(string $content_type): string|null
    {
        $mime_types = array(
            // images
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp'
        );

        if (array_key_exists($content_type, $mime_types)) {
            return $mime_types[$content_type];
        } else {
            return null;
        }
    }
}
