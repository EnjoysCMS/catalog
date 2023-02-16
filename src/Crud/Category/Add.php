<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config,
        private ContentEditor $contentEditor
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
    }

    /**
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

        return [
            'subtitle' => 'Добавление категории',
            'form' => $this->renderer,
            'editorEmbedCode' => $this->contentEditor
                ->withConfig($this->config->getEditorConfigCategoryDescription())
                ->setSelector('#description')
                ->getEmbedCode(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/category') => 'Категории',
                'Добавление новой категории',
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'parent' => $this->request->getQueryParams()['parent_id'] ?? null
            ]
        );


        $form->select('parent', 'Родительская категория')
            ->fill(
                ['0' => '_без родительской категории_'] + $this->entityManager->getRepository(
                    Category::class
                )->getFormFillArray()
            )
            ->addRule(Rules::REQUIRED)
        ;
        $form->text('title', 'Наименование')
            ->addRule(Rules::REQUIRED)
        ;

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $check = $this->categoryRepository->findOneBy(
                        [
                            'url' => $this->request->getParsedBody()['url'] ?? null,
                            'parent' => $this->categoryRepository->find(
                                $this->request->getParsedBody()['parent'] ?? null
                            )
                        ]
                    );
                    return is_null($check);
                }
            )
        ;

        $form->textarea('shortDescription', 'Короткое Описание');
        $form->textarea('description', 'Описание');
        $form->group('Изображение')
            ->add(
                [
                    new Text('img'),
                    new Html(
                        <<<HTML
<a class="btn btn-default btn-outline btn-upload"  id="inputImage" title="Upload image file">
    <span class="fa fa-upload "></span>
</a>
HTML
                    ),
                ]
            )
        ;
        $form->submit('add');
        return $form;
    }

    private function doAction(): void
    {
        /** @var Category|null $parent */
        $parent = $this->categoryRepository->find($this->request->getParsedBody()['parent'] ?? null);
        $category = new Category();
        $category->setParent($parent);
        $category->setSort(0);
        $category->setTitle($this->request->getParsedBody()['title'] ?? null);
        $category->setShortDescription($this->request->getParsedBody()['shortDescription'] ?? null);
        $category->setDescription($this->request->getParsedBody()['description'] ?? null);
        $category->setUrl($this->request->getParsedBody()['url'] ?? null);
        $category->setImg($this->request->getParsedBody()['img'] ?? null);

        $this->entityManager->persist($category);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
