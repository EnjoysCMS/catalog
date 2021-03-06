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
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
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
     * @param RendererInterface $renderer
     * @param EntityManager $entityManager
     * @param ServerRequestInterface $serverRequest
     * @param UrlGeneratorInterface $urlGenerator
     * @param ContainerInterface $container
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->serverRequest->get('id', 0)
        );
        if ($this->category === null) {
            Error::code(404);
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
            'subtitle' => '?????????????????? ??????????????????',
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }


    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);


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
            ->fill([1 => '???????????? ??????????????????'])
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
                    $url = $this->serverRequest->post('url');

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

        $linkFillFromParent = $this->category->getParent() ? '<a class="align-top btn btn-xs btn-warning"
                id="fill-from-parent"
                data-id="' . $this->category->getId() . '">
                ?????????????????? ???? ???????????????????????? ??????????????????</a>' : '';

        $linkFillAllChildren = $this->category->getChildren()->count() ? '<a class="align-top btn btn-xs btn-info"
                id="fill-all-children"
                href="' . $this->urlGenerator->generate(
                '@a/catalog/tools/category/set-extra-fields-to-children',
                ['id' => $this->category->getId()]
            ) . '">
                    ?????????????????? ?????? ???????????????? ??????????????????</a>' : '';


        $form->select(
            'extraFields',
            "???????????????????????????? ???????? {$linkFillFromParent} {$linkFillAllChildren} "
        )
            ->setDescription(
                '???????????????????????????? ????????, ?????????????? ?????????? ???????????????????? ?? ???????????? ??????????????????.
                ?????????????? ???? ???????????????????? ???????????? (??????????)'
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

    private function doAction()
    {
        $this->category->setTitle($this->serverRequest->post('title'));
        $this->category->setDescription($this->serverRequest->post('description'));
        $this->category->setShortDescription($this->serverRequest->post('shortDescription'));
        $this->category->setUrl($this->serverRequest->post('url'));
        $this->category->setStatus((bool)$this->serverRequest->post('status', false));
        $this->category->setImg($this->serverRequest->post('img'));

        $extraFields = $this->entityManager->getRepository(OptionKey::class)->findBy(
            ['id' => $this->serverRequest->post('extraFields', [])]
        );

        $this->category->removeExtraFields();
        foreach ($extraFields as $extraField) {
            $this->category->addExtraField($extraField);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
