<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;
    private ModuleConfig $config;

    public function __construct(private ContainerInterface $container)
    {
        $this->entityManager = $this->container->get(EntityManager::class);
        $this->serverRequest = $this->container->get(ServerRequestInterface::class);
        $this->renderer = $this->container->get(RendererInterface::class);
        $this->urlGenerator = $this->container->get(UrlGeneratorInterface::class);
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
        $this->config = Config::getConfig($this->container);
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

      //  dd(Setting::get('WYSIWYG'));
        $wysiwyg = WYSIWYG::getInstance($this->config->get('WYSIWYG'), $this->container);

        return [
            'subtitle' => '???????????????????? ??????????????????',
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


        $form->select('parent', '???????????????????????? ??????????????????')
            ->fill(
                ['0' => '_?????? ???????????????????????? ??????????????????_'] + $this->entityManager->getRepository(
                    Category::class
                )->getFormFillArray()
            )
            ->addRule(Rules::REQUIRED)
        ;
        $form->text('title', '????????????????????????')
            ->addRule(Rules::REQUIRED)
        ;

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                '????????????, ?????????? url ?????? ????????????????????',
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

        $form->textarea('shortDescription', '???????????????? ????????????????');
        $form->textarea('description', '????????????????');
        $form->group('??????????????????????')
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
        $category->setShortDescription($this->serverRequest->post('shortDescription'));
        $category->setDescription($this->serverRequest->post('description'));
        $category->setUrl($this->serverRequest->post('url'));
        $category->setImg($this->serverRequest->post('img'));

        $this->entityManager->persist($category);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
