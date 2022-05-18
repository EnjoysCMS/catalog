<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteUrl implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    private Url $url;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
        $this->url = $this->product->getUrlById((int)$this->requestWrapper->getQueryData('url_id'));


    }

    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->requestWrapper->getQueryData('product_id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);

        return [
            'product' => $this->product,
            'form' => $this->renderer->output(),
            'subtitle' => 'Удаление URL',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                $this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]
                ) => 'Менеджер URLs',
                'Удаление ссылки',
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->header(sprintf('Удалить ссылку: %s?', $this->url->getPath()));
        $form->submit('save', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        if($this->url->isDefault()){
            throw new \InvalidArgumentException('You cannot delete the main link');
        }
        $this->em->remove($this->url);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]));
    }
}
