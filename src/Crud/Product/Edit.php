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
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductUnit;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Events\PostEditProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreEditProductEvent;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Edit implements ModelInterface
{

    private Product $product;
    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository;
    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;


    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
        private Config $config,
        private ContentEditor $contentEditor,
        private EventDispatcherInterface $dispatcher,
    ) {
        $this->productRepository = $em->getRepository(Product::class);
        $this->categoryRepository = $em->getRepository(Category::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }

    /**
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
        $this->renderer->setOptions([
            'custom-switch' => true
        ]);

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(
                new PreEditProductEvent($oldProduct = clone $this->product)
            );
            $this->doAction();
            $this->dispatcher->dispatch(new PostEditProductEvent($oldProduct, $this->product));
            $this->redirect->toRoute('catalog/admin/products', emit: true);
        }

        return [
            'form' => $this->renderer,
            'product' => $this->product,
            'subtitle' => 'Редактирование',
            'editorEmbedCode' => $this->contentEditor
                ->withConfig($this->config->getEditorConfigProductDescription())
                ->setSelector('#description')
                ->getEmbedCode(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Редактирование общей информации `%s`', $this->product->getName()),
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     * @throws Exception
     */
    private function getForm(): Form
    {
        $defaults = [
            'name' => $this->product->getName(),
            'productCode' => $this->product->getProductCode(),
            'url' => $this->product->getUrl()->getPath(),
            'description' => $this->product->getDescription(),
            'unit' => $this->product->getUnit()?->getName(),
            'active' => [(int)$this->product->isActive()],
            'hide' => [(int)$this->product->isHide()],
        ];

        $category = $this->product->getCategory();
        if ($category instanceof Category) {
            $defaults['category'] = $category->getId();
        }

        $form = new Form();

        $form->setDefaults($defaults);

        $form->checkbox('active')
            ->setPrefixId('active')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Включен?']);

        $form->checkbox('hide')
            ->setPrefixId('hide')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Скрыт?']);

        $form->select('category', 'Категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->categoryRepository->getFormFillArray()
            );

        $form->text('name', 'Наименование')
            ->addRule(Rules::REQUIRED);

        $productCodeElem = $form->text('productCode', 'Уникальный код продукта')
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

        if ($this->config->get('disableEditProductCode', false)) {
            $productCodeElem->setAttribute(AttributeFactory::create('disabled'));
        }

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $category = $this->categoryRepository->find($this->request->getParsedBody()['category'] ?? 0);

                    try {
                        /** @var Product $product */
                        $product = $this->productRepository->getFindByUrlBuilder(
                            $this->request->getParsedBody()['url'] ?? null,
                            $category
                        )->getQuery()->getOneOrNullResult();
                    } catch (NonUniqueResultException) {
                        return false;
                    }

                    if ($product === null) {
                        return true;
                    }

                    /** @var Url $url */
                    foreach ($product->getUrls() as $url) {
                        if ($url->getProduct()->getId() === $this->product->getId()) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        $form->textarea('description', 'Описание');

        $form->text('unit', 'Единица измерения');


        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        /** @var Category|null $category */
        $category = $this->em->getRepository(Category::class)->find(
            $this->request->getParsedBody()['category'] ?? 0
        );

        $this->product->setName($this->request->getParsedBody()['name'] ?? null);

        if (!$this->config->get('disableEditProductCode', false)) {
            $productCode = $this->request->getParsedBody()['productCode'] ?? null;
            $this->product->setProductCode(empty($productCode) ? null : $productCode);
        }

        $this->product->setDescription($this->request->getParsedBody()['description'] ?? null);


        $unitValue = $this->request->getParsedBody()['unit'] ?? null;
        $unit = $this->em->getRepository(ProductUnit::class)->findOneBy(['name' => $unitValue]);
        if ($unit === null) {
            $unit = new ProductUnit();
            $unit->setName($unitValue);
            $this->em->persist($unit);
            $this->em->flush();
        }
        $this->product->setUnit($unit);

        $this->product->setCategory($category);
        $this->product->setActive((bool)($this->request->getParsedBody()['active'] ?? false));
        $this->product->setHide((bool)($this->request->getParsedBody()['hide'] ?? false));


        $urlString = (empty($this->request->getParsedBody()['url'] ?? null))
            ? URLify::slug($this->product->getName())
            : $this->request->getParsedBody()['url'] ?? null;

        /** @var Url $url */
        $urlSetFlag = false;
        foreach ($this->product->getUrls() as $url) {
            if ($url->getPath() === $urlString) {
                $url->setDefault(true);
                $urlSetFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($urlSetFlag === false) {
            $url = new Url();
            $url->setPath($urlString);
            $url->setDefault(true);
            $url->setProduct($this->product);
            $this->em->persist($url);
        }

        $this->em->flush();
    }
}
