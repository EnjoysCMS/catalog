<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\PersistentCollection;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;

final class SetExtraFieldsToChildren
{

    private Category $category;

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Category $categoryRepository;


    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RedirectInterface $redirect,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ExceptionRule
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doActionRecursive($this->category->getChildren());
            $this->redirect->toRoute('@catalog_admin_category_list', emit: true);
        }

        $this->renderer->setForm($form);

        return [
            'title' => sprintf("Установка extra fields из %s в  дочерние категории", $this->category->getTitle()),
            'subtitle' => 'Установка extra fields',
            'form' => $this->renderer,
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setMethod('post');
        $form->checkbox('removeOldExtraFields')->fill(
            [1 => 'Удалить у дочерних категории все установленные extra fields и записать новые']
        );
        $form->submit('setExtraFields', 'Установить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doActionRecursive(ArrayCollection|PersistentCollection $collection): void
    {
        $extraFields = $this->category->getExtraFields();

        /** @var Category $item */
        foreach ($collection as $item) {
            if ($item->getChildren()->count()) {
                $this->doActionRecursive($item->getChildren());
            }


            if ($this->request->getParsedBody()['removeOldExtraFields'] ?? false) {
                $item->removeExtraFields();
            }
            foreach ($extraFields->toArray() as $optionKey) {
                $item->addExtraField($optionKey);
            }

            $this->em->persist($item);
            $this->em->flush();
        }
    }
}
