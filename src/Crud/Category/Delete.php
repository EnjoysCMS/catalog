<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{
    private ?Category $category;
    /**
     * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;

    public function __construct(
        private EntityManager $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private ServerRequestInterface $request
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
        $this->category = $this->categoryRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        );
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        return [
            'title' => $this->category->getTitle(),
            'subtitle' => 'Удаление категории',
            'form' => $this->renderer
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
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

    private function doAction(): void
    {
        $setCategory = (($this->request->getParsedBody()['set_parent_category'] ?? null) !== null) ? $this->category->getParent() : null;

        if (($this->request->getParsedBody()['remove_childs'] ?? null) !== null) {
            $allCategoryIds = $this->entityManager->getRepository(Category::class)->getAllIds($this->category);
            $products = $this->entityManager->getRepository(Product::class)->findByCategorysIds($allCategoryIds);
            $this->setCategory($products, $setCategory);

            $this->entityManager->remove($this->category);
            $this->entityManager->flush();
        } else {
            $products = $this->entityManager->getRepository(Product::class)->findByCategory($this->category);
            $this->setCategory($products, $setCategory);

            $this->categoryRepository->removeFromTree($this->category);
            $this->categoryRepository->updateLevelValues();
            $this->entityManager->clear();
        }
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }

    private function setCategory($products, ?Category $category = null): void
    {
        foreach ($products as $product) {
            $product->setCategory($category);
        }
        $this->entityManager->flush();
    }
}
