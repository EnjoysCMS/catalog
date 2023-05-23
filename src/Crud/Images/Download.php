<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Config;
use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\ServerRequestInterface;

final class Download implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;
    private FilesystemOperator $filesystem;
    private ThumbnailService\ThumbnailServiceInterface $thumbnailService;


    public function __construct(private Config $config)
    {
        $this->filesystem = $this->config->getImageStorageUpload()->getFileSystem();
        $this->thumbnailService = $this->config->getThumbnailService();
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
        $this->thumbnailService->make($this->filesystem, $targetPath, $data);
    }

    /**
     * @throws Exception
     */
    private function makeDirectory(string $directory): void
    {
        if (preg_match("/(\/\.+|\.+)$/i", $directory)) {
            throw new Exception(
                sprintf("Нельзя создать директорию: %s", $directory)
            );
        }

        //Clear the most recent error
        error_clear_last();

        if (!is_dir($directory)) {
            if (@mkdir($directory, 0777, true) === false) {
                /** @var string[] $error */
                $error = error_get_last();
                throw new Exception(
                    sprintf("Не удалось создать директорию: %s! Причина: %s", $directory, $error['message'])
                );
            }
        }
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
