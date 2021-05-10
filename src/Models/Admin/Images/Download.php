<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;


use Enjoys\Forms\Form;
use Enjoys\Http\ServerRequestInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

final class Download implements LoadImage
{
    private string $name;
    private string $extension;
    private string $fullPathFileNameWithExtension;
    private string $uploadDir;


    public function __construct()
    {
        $this->uploadDir = rtrim($_ENV['UPLOAD_DIR'], '/') . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    private function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    private function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return string
     */
    public function getFullPathFileNameWithExtension(): string
    {
        return $this->fullPathFileNameWithExtension;
    }

    /**
     * @param string $fullPathFileNameWithExtension
     */
    private function setFullPathFileNameWithExtension(string $fullPathFileNameWithExtension): void
    {
        $this->fullPathFileNameWithExtension = $fullPathFileNameWithExtension;
    }

    public function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->text('image', 'Ссылка на изображение');

        $form->submit('download');
        return $form;
    }


    public function upload(ServerRequestInterface $serverRequest): void
    {

        $client = new Client(
            [
                'verify' => false
            ]
        );
        $response = $client->get($serverRequest->post('image'));
        $data = $response->getBody()->getContents();
        $ext = $this->getExt($response->getHeaderLine('Content-Type'));
        $newName = md5((string)microtime(true));
        $this->setName($newName[0] . '/' . $newName[1] . '/' . $newName);
        $this->setExtension($ext);
        $this->setFullPathFileNameWithExtension($this->uploadDir  . $this->getName() . '.' . $this->getExtension());

        $directory = pathinfo($this->getFullPathFileNameWithExtension(), PATHINFO_DIRNAME);
        $this->makeDirectory($directory);

        file_put_contents($this->getFullPathFileNameWithExtension(), $data);
    }

    private function makeDirectory(string $directory)
    {


        if (preg_match("/(\/\.+|\.+)$/i", $directory)) {
            throw new \Exception(
                sprintf("Нельзя создать директорию: %s", $directory)
            );
        }

        //Clear the most recent error
        error_clear_last();

        if (!is_dir($directory)) {
            if (@mkdir($directory, 0777, true) === false) {
                /** @var string[] $error */
                $error = error_get_last();
                throw new \Exception(
                    sprintf("Не удалось создать директорию: %s! Причина: %s", $directory, $error['message'])
                );
            }

        }
    }

    private function getExt($content_type)
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
            'image/svg+xml' => 'svgz',
        );

        if (array_key_exists($content_type, $mime_types)) {
            return $mime_types[$content_type];
        } else {
            return null;
        }
    }
}