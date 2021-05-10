<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\UploadFileSystem;
use Exception;
use Intervention\Image\ImageManagerStatic;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Upload\File;
use Upload\Storage\Base;


final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private ?Product $product;
    /**
     * @var mixed[]|object[]
     */
    private array $productImages;
    /**
     * @var mixed|string
     */
    private $uploadMethod;

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

        $this->productImages = $entityManager->getRepository(Image::class)->findBy(['product' => $this->product]);

        $method = $serverRequest->get('method');

        if (!in_array($method, ['upload', 'download'], true)) {
            $method = 'upload';
        }

        $method = '\EnjoysCMS\Module\Catalog\Models\Admin\Images\\'.ucfirst($method);

        $this->uploadMethod = new $method();
    }

    public function getContext(): array
    {
        $form = $this->uploadMethod->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        return [
            'form' => $this->renderer
        ];
    }


    private function doAction()
    {
        $this->uploadMethod->upload($this->serverRequest);

        $image = new Image();
        $image->setProduct($this->product);
        $image->setFilename($this->uploadMethod->getName());
        $image->setExtension($this->uploadMethod->getExtension());
        $image->setGeneral(empty($this->productImages));

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $imgSmall = ImageManagerStatic::make($this->uploadMethod->getFullPathFileNameWithExtension());
        $imgSmall->resize(
            300,
            300,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $imgSmall->save(
            str_replace(
                $this->uploadMethod->getName(),
                $this->uploadMethod->getName() . '_small',
                $this->uploadMethod->getFullPathFileNameWithExtension()
            )
        );

        $imgLarge = ImageManagerStatic::make($this->uploadMethod->getFullPathFileNameWithExtension());
        $imgLarge->resize(
            900,
            900,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $imgLarge->save(
            str_replace(
                $this->uploadMethod->getName(),
                $this->uploadMethod->getName() . '_large',
                $this->uploadMethod->getFullPathFileNameWithExtension()
            )
        );

        Redirect::http(
            $this->urlGenerator->generate(
                'catalog/admin/product/images',
                ['product_id' => $this->product->getId()]
            )
        );
    }

}