<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Events\PostDeleteCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PreDeleteCategoryEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{
    private Category $category;
    private \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository;
    private \EnjoysCMS\Module\Catalog\Repositories\Product|EntityRepository $productRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private ServerRequestInterface $request,
        private RedirectInterface $redirect,
        private EventDispatcherInterface $dispatcher,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->category = $this->categoryRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreDeleteCategoryEvent($this->category));
            $this->doAction();
            $this->dispatcher->dispatch(new PostDeleteCategoryEvent($this->category));
            $this->redirect->toRoute('catalog/admin/category', emit: true);
        }

        return [
            'title' => $this->category->getTitle(),
            'subtitle' => 'Удаление категории',
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/category') => 'Категории',
                sprintf('Удаление категории `%s`', $this->category->getTitle()),
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'set_parent_category' => [0]
        ]);
        $form->header('Подтвердите удаление!');
        $form->checkbox('remove_childs')->fill(['+ Удаление дочерних категорий']);
        $form->checkbox('set_parent_category')->setPrefixId('set_parent_category')->fill(
            [
                sprintf(
                    'Установить для продуктов из удаляемых категорий родительскую категорию (%s)',
                    $this->category->getParent()?->getTitle()
                )
            ]
        );
        $form->submit('delete', 'Удалить')->addClass('btn btn-danger');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    private function doAction(): void
    {
        $setCategory = (($this->request->getParsedBody(
            )['set_parent_category'] ?? null) !== null) ? $this->category->getParent() : null;

        if (($this->request->getParsedBody()['remove_childs'] ?? null) !== null) {
            $allCategoryIds = $this->categoryRepository->getAllIds($this->category);
            $products = $this->productRepository->findByCategorysIds($allCategoryIds);
            $this->setCategory($products, $setCategory);

            $this->em->remove($this->category);
            $this->em->flush();
        } else {
            $products = $this->productRepository->findByCategory($this->category);
            $this->setCategory($products, $setCategory);

            $this->categoryRepository->removeFromTree($this->category);
            $this->categoryRepository->updateLevelValues();
            $this->em->clear();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function setCategory($products, ?Category $category = null): void
    {
        foreach ($products as $product) {
            $product->setCategory($category);
        }
        $this->em->flush();
    }
}
