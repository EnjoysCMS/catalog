<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Exception;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Add implements ModelInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository;
    private ObjectRepository|EntityRepository $categoryRepository;

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Container $container,
        private Config $config,
        private Cookie $cookie
    ) {
        $this->productRepository = $em->getRepository(Product::class);
        $this->categoryRepository = $em->getRepository(Category::class);
    }

    /**
     * @return array
     * @throws Exception
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $wysiwyg = WYSIWYG::getInstance($this->config->getModuleConfig()->get('WYSIWYG'), $this->container);

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                'Добавление товара',
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'category' => $this->request->getQueryParams()['category_id']
                    ?? Cookie::get('__catalog__last_category_when_add_product')
            ]
        );


        $form->select('category', 'Категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                ['0' => '_без категории_'] + $this->em->getRepository(
                    Category::class
                )->getFormFillArray()
            );
        $form->text('name', 'Наименование')
            ->addRule(Rules::REQUIRED);


        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $check = $this->productRepository->getFindByUrlBuilder(
                        $this->request->getParsedBody()['url'] ?? null,
                        $this->categoryRepository->find($this->request->getParsedBody()['category'] ?? 0)
                    )->getQuery()->getOneOrNullResult();
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
    private function doAction(): void
    {
        $categoryId = $this->request->getParsedBody()['category'] ?? 0;
        $this->cookie->set('__catalog__last_category_when_add_product', $categoryId);

        /** @var Category|null $category */
        $category = $this->em->getRepository(Category::class)->find($categoryId);

        $product = new Product();
        $product->setName($this->request->getParsedBody()['name'] ?? null);
        $product->setDescription($this->request->getParsedBody()['description'] ?? null);

        $product->setCategory($category);

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
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}
