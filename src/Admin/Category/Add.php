<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Category;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;

final class Add
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Category $repository;


    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request
    ) {
        $this->repository = $this->em->getRepository(Category::class);
    }


    /**
     * @throws ExceptionRule
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    public function getForm(): Form
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
                ['0' => '_без родительской категории_'] + $this->repository->getFormFillArray()
            );

        $form->text('title', 'Наименование')
            ->addRule(Rules::REQUIRED);

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    $check = $this->repository->findOneBy(
                        [
                            'url' => $this->request->getParsedBody()['url'] ?? null,
                            'parent' => $this->repository->find(
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
            ->setDescription(
                '(Не обязательно) Путь к шаблону или другая информация, способная поменять отображение товаров в группе'
            );

        $form->text('meta-title', 'meta-title');
        $form->textarea('meta-description', 'meta-description');
        $form->text('meta-keywords', 'meta-keywords');

        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): Category
    {
        $category = new Category();
        $category->setParent($this->repository->find($this->request->getParsedBody()['parent'] ?? null));
        $category->setSort(0);
        $category->setTitle($this->request->getParsedBody()['title'] ?? null);
        $category->setShortDescription($this->request->getParsedBody()['shortDescription'] ?? null);
        $category->setDescription($this->request->getParsedBody()['description'] ?? null);
        $category->setUrl($this->request->getParsedBody()['url'] ?? null);
        $category->setImg($this->request->getParsedBody()['img'] ?? null);
        $category->setCustomTemplatePath($this->request->getParsedBody()['customTemplatePath'] ?? null);

        $meta = $category->getMeta();
        $meta->setTitle($this->request->getParsedBody()['meta-title'] ?? null);
        $meta->setDescription($this->request->getParsedBody()['meta-description'] ?? null);
        $meta->setKeyword($this->request->getParsedBody()['meta-keywords'] ?? null);
        $this->em->persist($meta);

        $category->setMeta($meta);

        $this->em->persist($category);
        $this->em->flush();
        return $category;
    }
}
