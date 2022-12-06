<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SetExtraFieldsToChildren implements ModelInterface
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
        private UrlGeneratorInterface $urlGenerator
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

    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doActionRecursive($this->category->getChildren());
            Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
        }

        $this->renderer->setForm($form);

        return [
            'title' => sprintf("Установка extra fields из %s в  дочерние категории", $this->category->getTitle()),
            'subtitle' => 'Установка extra fields',
            'form' => $this->renderer,
        ];
    }

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

            $this->entityManager->persist($item);
            $this->entityManager->flush();

        }
    }
}
