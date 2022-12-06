<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EditUrl implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    private Url $url;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
        $this->url = $this->product->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? 0));
    }

    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->request->getQueryParams()['product_id'] ?? null);
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws OptimisticLockException
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
            'subtitle' => 'Редактирование URL',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                $this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]
                ) => 'Менеджер URLs',
                'Редактирование ссылки',
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'path' => $this->url->getPath()
        ]);
        $form->text('path', 'Путь')->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    /** @var Product $product */
                    $product = $this->productRepository->getFindByUrlBuilder(
                        $this->request->getParsedBody()['path'] ?? null,
                        $this->product->getCategory()
                    )->getQuery()->getOneOrNullResult();

                    if ($product === null) {
                        return true;
                    }

                    if ($this->url->getPath() === ($this->request->getParsedBody()['path'] ?? null)){
                        return true;
                    }

                    return false;
                }
            )
        ;
        $form->submit('save', 'Сохранить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->url->setPath($this->request->getParsedBody()['path'] ?? null);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]));
    }
}
