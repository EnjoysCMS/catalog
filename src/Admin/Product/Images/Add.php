<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Images;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;


final class Add
{

    private Product $product;

    private LoadImage $uploadMethod;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect,
        private readonly Config $config,
        FactoryInterface $factory
    ) {
        $this->product = $entityManager->getRepository(Product::class)->find(
            $this->request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();

        $method = $this->request->getQueryParams()['method'] ?? 'upload';

        if (!in_array($method, ['upload', 'download'], true)) {
            $method = 'upload';
        }

        /** @var class-string<LoadImage> $method */
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

                $this->redirect->toRoute(
                    'catalog/admin/product/images',
                    ['product_id' => $this->product->getId()],
                    emit: true
                );
            } catch (Throwable $e) {
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
                $this->urlGenerator->generate('@catalog_admin') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Добавление нового изображения: `%s`', $this->product->getName()),
            ],
        ];
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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
