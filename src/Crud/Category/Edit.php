<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use DI\DependencyException;
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
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private ?Category $category;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;


    /**
     * @throws NotFoundException
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config,
        private ContentEditor $contentEditor
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        );
        if ($this->category === null) {
            throw new NotFoundException(
                sprintf('Not found by id: %s', $this->request->getQueryParams()['id'] ?? '0')
            );
        }
    }

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }


        return [
            'title' => $this->category->getTitle(),
            'subtitle' => 'Изменение категории',
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
                    $url = $this->request->getParsedBody()['url'] ?? null;

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
        $this->category->setTitle($this->request->getParsedBody()['title'] ?? null);
        $this->category->setDescription($this->request->getParsedBody()['description'] ?? null);
        $this->category->setShortDescription($this->request->getParsedBody()['shortDescription'] ?? null);
        $this->category->setUrl($this->request->getParsedBody()['url'] ?? null);
        $this->category->setStatus((bool)($this->request->getParsedBody()['status'] ?? false));
        $this->category->setImg($this->request->getParsedBody()['img'] ?? null);

        $extraFields = $this->entityManager->getRepository(OptionKey::class)->findBy(
            ['id' => $this->request->getParsedBody()['extraFields'] ?? null]
        );

        $this->category->removeExtraFields();
        foreach ($extraFields as $extraField) {
            $this->category->addExtraField($extraField);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
