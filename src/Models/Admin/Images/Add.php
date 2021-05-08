<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use HttpSoft\Message\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        RendererInterface $renderer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        return [
            'form' => $this->renderer
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->file('image', 'Изображение');

        $form->submit('upload');
        return $form;
    }

    private function doAction()
    {
        $upload_dir =  $_ENV['UPLOAD_DIR'];
        /** @var \HttpSoft\Message\UploadedFile $uploadedFile */
        $uploadedFile = $this->serverRequest->files('image');
        $newName = md5($uploadedFile->getClientFilename() . time());
        foreach ([$newName[0], $newName[1]] as $dir) {
          //  $path_for_name .= $dir . DIRECTORY_SEPARATOR;
            $upload_dir .= DIRECTORY_SEPARATOR . $dir;
            if (!file_exists($upload_dir)) {
                // dump($upload_dir);
                if (!mkdir($upload_dir, 0777, true)) {
                    return false;
                }
            }
        }

        var_dump($uploadedFile->getClientMediaType());

        exit;
        //Redirect::http($this->urlGenerator->generate('catalog/admin/images'));
    }
}