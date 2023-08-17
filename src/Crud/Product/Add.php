<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Exception;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Events\PostAddProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreAddProductEvent;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository;
    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;

    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect,
        private readonly Config $config,
        private readonly Cookie $cookie,
        private readonly ContentEditor $contentEditor,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        $this->productRepository = $em->getRepository(Product::class);
        $this->categoryRepository = $em->getRepository(Category::class);
    }


    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws NotFoundException
     * @throws Exception
     * @throws DependencyException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreAddProductEvent());
            $product = $this->doAction();
            $this->dispatcher->dispatch(new PostAddProductEvent($product));
            $this->redirect->toRoute('catalog/admin/products', emit: true);
        }

        return [
            'form' => $this->renderer,
            'editorEmbedCode' => $this->contentEditor
                ->withConfig($this->config->getEditorConfigProductDescription())
                ->setSelector('#description')
                ->getEmbedCode(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                'Добавление товара',
            ],
        ];
    }


    /**
     * @throws ExceptionRule
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws QueryException
     */
    private function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'category' => $this->request->getQueryParams()['category_id']
                    ?? $this->cookie->get('__catalog__last_category_when_add_product')
            ]
        );


        $form->select('category', 'Категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                ['0' => '_без категории_'] + $this->categoryRepository->getFormFillArray()
            );
        $form->text('name', 'Наименование')
            ->addRule(Rules::REQUIRED);

        $form->text('productCode', 'Уникальный код продукта')
            ->setDescription(
                'Не обязательно. Уникальный идентификатор продукта, уникальный артикул, внутренний код
            в системе учета или что-то подобное, используется для внутренних команд и запросов,
            но также можно и показывать это поле наружу'
            )
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, productCode уже используется',
                function () {
                    $check = $this->productRepository->findOneBy(
                        ['productCode' => $this->request->getParsedBody()['productCode'] ?? '']
                    );
                    return is_null($check);
                }
            );


        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    try {
                        $check = $this->productRepository->getFindByUrlBuilder(
                            $this->request->getParsedBody()['url'] ?? null,
                            $this->categoryRepository->find($this->request->getParsedBody()['category'] ?? 0)
                        )->getQuery()->getOneOrNullResult();
                    } catch (NonUniqueResultException) {
                        return false;
                    }

                    return is_null($check);
                }
            );
        $form->textarea('description', 'Описание');

        $form->submit('add');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function doAction(): Product
    {
        $categoryId = $this->request->getParsedBody()['category'] ?? 0;
        $this->cookie->set('__catalog__last_category_when_add_product', $categoryId);

        /** @var Category|null $category */
        $category = $this->em->getRepository(Category::class)->find($categoryId);

        $product = new Product();
        $product->setName($this->request->getParsedBody()['name'] ?? null);
        $product->setDescription($this->request->getParsedBody()['description'] ?? null);

        $product->setCategory($category);

        $productCode = $this->request->getParsedBody()['productCode'] ?? null;
        $product->setProductCode(empty($productCode) ? null : $productCode);

        $product->setHide(false);
        $product->setActive(true);

        $this->em->persist($product);
        $this->em->flush();

        $url = new Url();
        $url->setProduct($product);
        $url->setDefault(true);
        $url->setPath(
            (empty($this->request->getParsedBody()['url'] ?? null))
                ? URLify::slug($product->getName())
                : $this->request->getParsedBody()['url'] ?? null
        );

        $this->em->persist($url);
        $this->em->flush();

        $product->addUrl($url);
        return $product;
    }
}
