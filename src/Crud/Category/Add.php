<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config,
        private ContentEditor $contentEditor,
        private RedirectInterface $redirect,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
    }

    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->em->flush();
            $this->redirect->http($this->urlGenerator->generate('catalog/admin/category'), emit: true);
        }

        return [
            'subtitle' => 'Добавление категории',
            'form' => $this->renderer,
            'editorEmbedCode' => $this->contentEditor
                    ->withConfig($this->config->getEditorConfigCategoryDescription())
                    ->setSelector('#description')
                    ->getEmbedCode()
                . $this->contentEditor
                    ->withConfig($this->config->getEditorConfigCategoryShortDescription())
                    ->setSelector('#shortDescription')
                    ->getEmbedCode(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/category') => 'Категории',
                'Добавление новой категории',
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    private function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'parent' => $this->request->getQueryParams()['parent_id'] ?? null
            ]
        );


        $form->select('parent', 'Родительская категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                ['0' => '_без родительской категории_'] + $this->categoryRepository->getFormFillArray()
            );

        $form->text('title', 'Наименование')
            ->addRule(Rules::REQUIRED);

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
            );

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
            );

        $form->text('customTemplatePath', 'Пользовательский шаблон отображения категории')
            ->setDescription('(Не обязательно) Путь к шаблону или другая информация, способная поменять отображение товаров в группе');


        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $category = new Category();
        $category->setParent($this->categoryRepository->find($this->request->getParsedBody()['parent'] ?? null));
        $category->setSort(0);
        $category->setTitle($this->request->getParsedBody()['title'] ?? null);
        $category->setShortDescription($this->request->getParsedBody()['shortDescription'] ?? null);
        $category->setDescription($this->request->getParsedBody()['description'] ?? null);
        $category->setUrl($this->request->getParsedBody()['url'] ?? null);
        $category->setImg($this->request->getParsedBody()['img'] ?? null);
        $category->setCustomTemplatePath($this->request->getParsedBody()['customTemplatePath'] ?? null);

        $this->em->persist($category);
    }
}
