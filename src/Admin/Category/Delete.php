<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\Product;
use Psr\Http\Message\ServerRequestInterface;

final class Delete
{
    private Category $category;
    private \EnjoysCMS\Module\Catalog\Repository\Category|EntityRepository $categoryRepository;
    private \EnjoysCMS\Module\Catalog\Repository\Product|EntityRepository $productRepository;


    /**
     * @throws NotSupported
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->category = $this->categoryRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }


    public function getForm(): Form
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
                    $this->category->getParent()?->getTitle() ?? 'без родительской категории'
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
    public function doAction(): void
    {
        $setCategory = (($this->request->getParsedBody(
            )['set_parent_category'] ?? null) !== null) ? $this->category->getParent() : null;

        $this->em->remove($this->category->getMeta());

        if (($this->request->getParsedBody()['remove_childs'] ?? null) !== null) {
            /** @var array $allCategoryIds */
            $allCategoryIds = $this->categoryRepository->getAllIds($this->category);
            /** @var Product[] $products */
            $products = $this->productRepository->findByCategorysIds($allCategoryIds);
            $this->setCategory($products, $setCategory);

            $this->em->remove($this->category);
            $this->em->flush();
        } else {
            /** @var Product[] $products */
            $products = $this->productRepository->findByCategory($this->category);
            $this->setCategory($products, $setCategory);

            $this->categoryRepository->removeFromTree($this->category);
            $this->categoryRepository->updateLevelValues();
            $this->em->clear();
        }
    }

    /**
     * @param Product[] $products
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function setCategory(array $products, ?Category $category = null): void
    {
        foreach ($products as $product) {
            $product->setCategory($category);
        }
        $this->em->flush();
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
