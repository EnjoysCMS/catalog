<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private Environment $twig;
    /**
     * @var EntityRepository|ObjectRepository
     */
    private $productRepository;
    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;
    private ContainerInterface $container;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        RendererInterface $renderer,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;

        $this->productRepository = $entityManager->getRepository(Product::class);
        $this->categoryRepository = $entityManager->getRepository(Category::class);

        $this->container = $container;
        $this->config = Config::getConfig($this->container);
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $wysiwyg = WYSIWYG::getInstance($this->config->get('WYSIWYG'), $this->container);

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->setDefaults(
            [
                'category' => $this->serverRequest->get('category_id')
            ]
        );


        $form->select('category', 'Категория')
            ->fill(
                $this->entityManager->getRepository(
                    Category::class
                )->getFormFillArray()
            )
            ->addRule(Rules::REQUIRED)
        ;
        $form->text('name', 'Наименование')
            ->addRule(Rules::REQUIRED)
        ;

        $form->text('articul', 'Артикул');

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $check = $this->productRepository->findOneBy(
                        [
                            'url' => $this->serverRequest->post('url'),
                            'category' => $this->categoryRepository->find($this->serverRequest->post('category', 0))
                        ]
                    );
                    return is_null($check);
                }
            )
        ;
        $form->textarea('description', 'Описание');

        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction()
    {
        $category = $this->entityManager->getRepository(Category::class)->find($this->serverRequest->post('category', 0));

        $product = new Product();
        $product->setName($this->serverRequest->post('name'));
        $product->setDescription($this->serverRequest->post('description'));
        $product->setArticul($this->serverRequest->post('articul'));
        /** @var Category $category */
        $product->setCategory($category);
        $product->setUrl(
            (empty($this->serverRequest->post('url')))
                ? URLify::slug($product->getName())
                : $this->serverRequest->post('url')
        );
        $product->setHide(false);
        $product->setActive(true);

        $this->entityManager->persist($product);

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}