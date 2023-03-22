<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddUrl implements ModelInterface
{
    private EntityRepository|ProductRepository $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
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
            'subtitle' => 'Добавление URL',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                $this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]
                ) => 'Менеджер URLs',
                'Добавление ссылки',
            ],
        ];
    }


    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();

        $form->checkbox('default')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Сделать основным?']);

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

                    return is_null($product);
                }
            );
        $form->submit('save', 'Добавить');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $url = new Url();
        $url->setPath($this->request->getParsedBody()['path'] ?? null);
        $url->setDefault((bool)($this->request->getParsedBody()['default'] ?? false));
        $url->setProduct($this->product);

        if ($url->isDefault()) {
            foreach ($this->product->getUrls() as $item) {
                $item->setDefault(false);
            }
        }

        $this->em->persist($url);
        $this->em->flush();
        $this->redirect->http(
            $this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]),
            emit: true
        );
    }
}
