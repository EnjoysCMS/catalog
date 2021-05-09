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
        //var_dump($this->productImages);
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

        $form->file('image', 'Изображение')
            ->addRule(
                Rules::UPLOAD,
                null,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 2,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )
            ->setAttribute('accept', '.png, .jpg, .jpeg')
        ;

        $form->submit('upload');
        return $form;
    }

    private function doAction()
    {

        $storage = new UploadFileSystem($_ENV['UPLOAD_DIR']);
        $file = new File('image', $storage);
        $this->upload($file, $storage);


        Redirect::http(
            $this->urlGenerator->generate(
                'catalog/admin/product/images',
                ['product_id' => $this->product->getId()]
            )
        );
    }

    private function upload(File $file, Base $storage)
    {
        $newName = md5((string)microtime(true));
        $file->setName($newName[0] . '/' . $newName[1] . '/' . $newName);
        try {
            $file->upload();

            $image = new Image();
            $image->setProduct($this->product);
            $image->setFilename($file->getName());
            $image->setExtension($file->getExtension());
            $image->setGeneral(empty($this->productImages));

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            $imgSmall = ImageManagerStatic::make($storage->getFullPathFileNameWithExtension());
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
                    $file->getName(),
                    $file->getName() . '_small',
                    $storage->getFullPathFileNameWithExtension()
                )
            );

            $imgLarge = ImageManagerStatic::make($storage->getFullPathFileNameWithExtension());
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
                    $file->getName(),
                    $file->getName() . '_large',
                    $storage->getFullPathFileNameWithExtension()
                )
            );
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage() . ' ' . implode(", ", $file->getErrors()));
        }
    }
}