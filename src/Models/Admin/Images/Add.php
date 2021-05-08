<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\UploadFileSystem;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Upload\File;


final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private ?Product $product;

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

        $this->product = $entityManager->getRepository(Product::class)->find($serverRequest->get('product_id'));
        if ($this->product === null) {
            Error::code(404);
        }
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
        $storage = new UploadFileSystem($_ENV['UPLOAD_DIR']);
        $file = new File('image', $storage);
        $newName = md5((string)microtime(true));
        $file->setName($newName[0] . '/' . $newName[1] . '/' . $newName);
        try {

            $file->upload();

            $image = new Image();
            $image->setProduct($this->product);
            $image->setFilename($file->getName());
            $image->setExtension($file->getExtension());

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            Redirect::http(
                $this->urlGenerator->generate(
                    'catalog/admin/product/images',
                    ['product_id' => $this->product->getId()]
                )
            );
        } catch (Exception $e) {
            throw new \InvalidArgumentException($e->getMessage() . ' ' . implode(", ", $file->getErrors()));
        }
    }
}