<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Files;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductFiles;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use EnjoysCMS\Module\Catalog\UploadFileSystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Upload\File;

final class Upload implements ModelInterface
{

    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private RendererInterface $renderer,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer,
            'subtitle' => 'Загрузка файла'
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->text('title', 'Наименование')->setDescription('Не обязательно');
        $form->file('file', 'Выберите файл')->addRule(Rules::UPLOAD, null, ['required']);
        $form->submit(uniqid('submit'));
        return $form;
    }

    private function doAction()
    {
        $storage = new UploadFileSystem(
            $_ENV['UPLOAD_DIR'] . DIRECTORY_SEPARATOR . 'catalog_files' . DIRECTORY_SEPARATOR, true
        );

        $file = new File('file', $storage);
        $file->setName($this->product->getId() . '/' . $file->getName());
        try {
            $file->upload();

            $productFile = new ProductFiles();
            $productFile->setProduct($this->product);
            $productFile->setFilename($file->getNameWithExtension());
            $productFile->setFilesize($this->requestWrapper->getFilesData('file')->getSize());
            $productFile->setTitle(
                empty($this->requestWrapper->getPostData('title'))
                    ? $this->requestWrapper->getFilesData('file')->getClientFilename()
                    : $this->requestWrapper->getPostData('title')
            );

            $this->em->persist($productFile);
            $this->em->flush();

            Redirect::http(
                $this->urlGenerator->generate('@a/catalog/product/files', [
                    'id' => $this->product->getId()
                ])
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException($e->getMessage() . ' ' . implode(", ", $file->getErrors()));
        }
    }
}