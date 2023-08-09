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
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function json_decode;

final class Index
{

    private \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository;


    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
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
            $this->redirect->toRoute('@catalog_admin_category_list', emit: true);
        }
        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer->output(),
            'categories' => $this->categoryRepository->getChildNodes(),
        ];
    }


}
