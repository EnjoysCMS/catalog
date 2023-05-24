<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function json_decode;

final class Index implements ModelInterface
{

    private \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository;


    /**
     * @throws NotSupported
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getContext(): array
    {
        $form = new Form();
        $form->hidden('nestable-output')->setAttribute(AttributeFactory::create('id', 'nestable-output'));
        $form->submit('save', 'Сохранить');


        if ($form->isSubmitted()) {
            (new SaveCategoryStructure($this->em))
            (
                json_decode($this->request->getParsedBody()['nestable-output'] ?? '')
            );

            $this->em->flush();
            $this->redirect->toRoute('catalog/admin/category', emit: true);
        }
        $this->renderer->setForm($form);


        return [
            'form' => $this->renderer->output(),
            'categories' => $this->categoryRepository->getChildNodes(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                'Категории',
            ],
        ];
    }


}
