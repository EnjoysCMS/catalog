<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
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

final class Edit implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private Environment $twig;
    private ?Product $product;
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $productRepository;
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
        $this->product = $this->productRepository->find(
            $this->serverRequest->get('id', 0)
        );
        if ($this->product === null) {
            Error::code(404);
        }
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

    private function getForm(): Form
    {
        $defaults = [
            'name' => $this->product->getName(),
            'url' => $this->product->getUrl(),
            'description' => $this->product->getDescription(),
        ];

        $category = $this->product->getCategory();
        if ($category instanceof Category) {
            $defaults['category'] = $category->getId();
        }

        $form = new Form(['method' => 'post']);

        $form->setDefaults($defaults);


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
        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $url = $this->serverRequest->post('url');
                    if ($url === $this->product->getUrl()) {
                        return true;
                    }
                    $check = $this->productRepository->findOneBy(
                        [
                            'url' => $url,
                            'category' => $this->product->getCategory()
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

    private function doAction()
    {
        /** @var Category|null $category */
        $category = $this->entityManager->getRepository(Category::class)->find(
            $this->serverRequest->post('category', 0)
        )
        ;

        $this->product->setName($this->serverRequest->post('name'));
        $this->product->setDescription($this->serverRequest->post('description'));
        $this->product->setCategory($category);
        $this->product->setUrl(
            (empty($this->serverRequest->post('url')))
                ? URLify::slug($this->product->getName())
                : $this->serverRequest->post('url')
        );

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}