<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private ?Category $category;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;
    private ModuleConfig $config;


    /**
     * @throws NotFoundException
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->requestWrapper->getQueryData('id', 0)
        );
        if ($this->category === null) {
            throw new NotFoundException(
                sprintf('Not found by id: %s', (string) $this->requestWrapper->getQueryData()->get('id', 0))
            );
        }

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
            'title' => $this->category->getTitle(),
            'subtitle' => 'Изменение категории',
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                '#' => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/category') => 'Категории',
                sprintf('Редактирование категории `%s`', $this->category->getTitle()),
            ],
        ];
    }


    private function getForm(): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
                'title' => $this->category->getTitle(),
                'description' => $this->category->getDescription(),
                'shortDescription' => $this->category->getShortDescription(),
                'url' => $this->category->getUrl(),
                'img' => $this->category->getImg(),
                'status' => [(int)$this->category->isStatus()],
                'extraFields' => array_map(
                    function ($item) {
                        return $item->getId();
                    },
                    $this->category->getExtraFields()->toArray()
                )
            ]
        );

        $form->checkbox('status', null)
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Статус категории'])
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
                    $url = $this->requestWrapper->getPostData('url');

                    if ($url === $this->category->getUrl()) {
                        return true;
                    }

                    $check = $this->categoryRepository->findOneBy(
                        [
                            'url' => $url,
                            'parent' => $this->category->getParent()
                        ]
                    );
                    return is_null($check);
                }
            )
        ;
        $form->textarea('shortDescription', 'Короткое описание');
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

        $linkFillFromParent = $this->category->getParent() ? '<a class="align-top btn btn-xs btn-warning"
                id="fill-from-parent"
                data-id="' . $this->category->getId() . '">
                заполнить из родительской категории</a>' : '';

        $linkFillAllChildren = $this->category->getChildren()->count() ? '<a class="align-top btn btn-xs btn-info"
                id="fill-all-children"
                href="' . $this->urlGenerator->generate(
                '@a/catalog/tools/category/set-extra-fields-to-children',
                ['id' => $this->category->getId()]
            ) . '">
                    заполнить все дочерние категории</a>' : '';


        $form->select(
            'extraFields',
            "Дополнительные поля {$linkFillFromParent} {$linkFillAllChildren} "
        )
            ->setDescription(
                'Дополнительные поля, которые можно отображать в списке продуктов.
                Берутся из параметров товара (опций)'
            )->addClass('set-extra-fields')
            ->setMultiple()
            ->fill(function () {
                $optionKeys = $this->entityManager->getRepository(OptionKey::class)->findBy(
                    [
                        'id' => array_map(
                            function ($item) {
                                return $item->getId();
                            },
                            $this->category->getExtraFields()->toArray()
                        )
                    ]
                );
                $result = [];
                foreach ($optionKeys as $key) {
                    $result[$key->getId()] = [
                        $key->getName() . (($key->getUnit()) ? ' (' . $key->getUnit() . ')' : ''),
                        ['id' => uniqid()]
                    ];
                }
                return $result;
            })
        ;

        $form->submit('add');
        return $form;
    }

    private function doAction(): void
    {
        $this->category->setTitle($this->requestWrapper->getPostData('title'));
        $this->category->setDescription($this->requestWrapper->getPostData('description'));
        $this->category->setShortDescription($this->requestWrapper->getPostData('shortDescription'));
        $this->category->setUrl($this->requestWrapper->getPostData('url'));
        $this->category->setStatus((bool)$this->requestWrapper->getPostData('status', false));
        $this->category->setImg($this->requestWrapper->getPostData('img'));

        $extraFields = $this->entityManager->getRepository(OptionKey::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('extraFields')]
        );

        $this->category->removeExtraFields();
        foreach ($extraFields as $extraField) {
            $this->category->addExtraField($extraField);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
