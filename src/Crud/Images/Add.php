<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


final class Add implements ModelInterface
{

    private ?Product $product;
    /**
     * @var mixed[]|object[]
     */
    private array $productImages;
    /**
     * @var mixed|string
     */
    private $uploadMethod;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        ContainerInterface $container
    ) {
        $this->product = $entityManager->getRepository(Product::class)->find(
            $this->requestWrapper->getQueryData('product_id')
        );
        if ($this->product === null) {
            throw new NotFoundException(
                sprintf('Not found by product_id: %s', $this->requestWrapper->getQueryData('product_id'))
            );
        }

        $this->productImages = $entityManager->getRepository(Image::class)->findBy(['product' => $this->product]);

        $method = $this->requestWrapper->getQueryData('method', 'upload');

        if (!in_array($method, ['upload', 'download'], true)) {
            $method = 'upload';
        }

        $method = '\EnjoysCMS\Module\Catalog\Crud\Images\\' . ucfirst($method);

        $this->uploadMethod = new $method(Config::getConfig($container)->get('manageUploads'));
    }

    public function getContext(): array
    {
        $form = $this->uploadMethod->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        return [
            'form' => $this->renderer,
            'product' => $this->product,
            'subtitle' => 'Загрузка изображения для продукта',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Добавление нового изображения: `%s`', $this->product->getName()),
            ],
        ];
    }


    private function doAction(): void
    {
        foreach ($this->uploadMethod->upload($this->requestWrapper) as $item) {
            $manageImage = new ManageImage($this->product, $this->entityManager);
            $manageImage->addToDB(
                $item->getName(),
                $item->getExtension(),
                $item->getFullPathFileNameWithExtension()
            );
        }


        Redirect::http(
            $this->urlGenerator->generate(
                'catalog/admin/product/images',
                ['product_id' => $this->product->getId()]
            )
        );
    }

}
