<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Category;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit
{

    private Category $category;

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Category $repository;


    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        $this->repository = $this->em->getRepository(Category::class);

        $this->category = $this->repository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }



    /**
     * @throws ExceptionRule
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getForm(): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
                'parent' => $this->category->getParent()?->getId(),
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
                ),
                'customTemplatePath' => $this->category->getCustomTemplatePath(),
                'meta-title' => $this->category->getMeta()->getTitle(),
                'meta-description' => $this->category->getMeta()->getDescription(),
                'meta-keywords' => $this->category->getMeta()->getKeyword(),
            ]
        );

        $form->checkbox('status')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Статус категории']);


        $form->select('parent', 'Родительская категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                ['0' => '_без родительской категории_'] + $this->repository->getFormFillArray(criteria: [
                    Criteria::create()->where(Criteria::expr()->neq('id', $this->category->getId()))
                ])
            );

        $form->text('title', 'Наименование')
            ->addRule(Rules::REQUIRED);

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

                    $check = $this->repository->findOneBy(
                        [
                            'url' => $url,
                            'parent' => $this->category->getParent()
                        ]
                    );
                    return is_null($check);
                }
            );
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
            );

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
            "Дополнительные поля $linkFillFromParent $linkFillAllChildren "
        )
            ->setDescription(
                'Дополнительные поля, которые можно отображать в списке продуктов.
                Берутся из параметров товара (опций)'
            )->addClass('set-extra-fields')
            ->setMultiple()
            ->fill(function () {
                $optionKeys = $this->em->getRepository(OptionKey::class)->findBy(
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
            });

        $form->text('customTemplatePath', 'Пользовательский шаблон отображения категории')
            ->setDescription('(Не обязательно) Путь к шаблону или другая информация, способная поменять отображение товаров в группе');


        $form->text('meta-title', 'meta-title');
        $form->textarea('meta-description', 'meta-description');
        $form->text('meta-keywords', 'meta-keywords');

        $form->submit('add');
        return $form;
    }


    /**
     * @throws NotSupported
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->category->setParent($this->repository->find($this->request->getParsedBody()['parent'] ?? 0));
        $this->category->setTitle($this->request->getParsedBody()['title'] ?? null);
        $this->category->setDescription($this->request->getParsedBody()['description'] ?? null);
        $this->category->setShortDescription($this->request->getParsedBody()['shortDescription'] ?? null);
        $this->category->setUrl($this->request->getParsedBody()['url'] ?? null);
        $this->category->setStatus((bool)($this->request->getParsedBody()['status'] ?? false));
        $this->category->setImg($this->request->getParsedBody()['img'] ?? null);
        $this->category->setCustomTemplatePath($this->request->getParsedBody()['customTemplatePath'] ?? null);

        $meta = $this->category->getMeta();
        $meta->setTitle($this->request->getParsedBody()['meta-title'] ?? null);
        $meta->setDescription($this->request->getParsedBody()['meta-description'] ?? null);
        $meta->setKeyword($this->request->getParsedBody()['meta-keywords'] ?? null);
        $this->em->persist($meta);

        $this->category->setMeta($meta);

        $extraFields = $this->em->getRepository(OptionKey::class)->findBy(
            ['id' => $this->request->getParsedBody()['extraFields'] ?? 0]
        );

        $this->category->removeExtraFields();
        foreach ($extraFields as $extraField) {
            $this->category->addExtraField($extraField);
        }
        $this->em->flush();
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
