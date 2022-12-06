<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use DI\DependencyException;
use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ServerRequestInterface;
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
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config,
        FactoryInterface $factory
    ) {
        $this->product = $entityManager->getRepository(Product::class)->find(
            $this->request->getQueryParams()['product_id'] ?? null
        );
        if ($this->product === null) {
            throw new NotFoundException(
                sprintf('Not found by product_id: %s', $this->request->getQueryParams()['product_id'] ?? null)
            );
        }

        $this->productImages = $entityManager->getRepository(Image::class)->findBy(['product' => $this->product]);

        $method = $this->request->getQueryParams()['method'] ?? 'upload';

        if (!in_array($method, ['upload', 'download'], true)) {
            $method = 'upload';
        }

        $method = '\EnjoysCMS\Module\Catalog\Crud\Images\\' . ucfirst($method);

        $this->uploadMethod = $factory->make($method);
    }

    public function getTemplatePath(string $templateRootPath): string
    {
        return $this->uploadMethod->getTemplatePath($templateRootPath);
    }

    public function getContext(): array
    {
        $form = $this->uploadMethod->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {

            try {
                $this->doAction();

                Redirect::http(
                    $this->urlGenerator->generate(
                        'catalog/admin/product/images',
                        ['product_id' => $this->product->getId()]
                    )
                );

            } catch (\Throwable $e) {
                /** @var File $image */
                $image = $form->getElement('image');
                $image->setRuleError(htmlspecialchars(sprintf('%s: %s', $e::class, $e->getMessage())));
            }
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
        foreach ($this->uploadMethod->upload($this->request) as $item) {
            $manageImage = new ManageImage($this->product, $this->entityManager, $this->config);
            $manageImage->addToDB(
                $item->getName(),
                $item->getExtension()
            );
        }


    }

}
