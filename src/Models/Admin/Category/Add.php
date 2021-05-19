<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Element;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
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
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $categoryRepository;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        RendererInterface $renderer,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;

        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $wysiwyg = new WYSIWYG(new Summernote());
        $wysiwyg->setTwig($this->twig);

        return [
            'subtitle' => 'Добавление категории',
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->setDefaults(
            [
                'parent' => $this->serverRequest->get('parent_id')
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
                            'url' => $this->serverRequest->post('url'),
                            'parent' => $this->categoryRepository->find($this->serverRequest->post('parent'))
                        ]
                    );
                    return is_null($check);
                }
            )
        ;

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

    private function doAction()
    {
        /** @var Category|null $parent */
        $parent = $this->categoryRepository->find($this->serverRequest->post('parent'));
        $category = new Category();
        $category->setParent($parent);
        $category->setSort(0);
        $category->setTitle($this->serverRequest->post('title'));
        $category->setDescription($this->serverRequest->post('description'));
        $category->setUrl($this->serverRequest->post('url'));
        $category->setImg($this->serverRequest->post('img'));

        $this->entityManager->persist($category);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
