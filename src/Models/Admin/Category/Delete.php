<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequest;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{
    private EntityManager $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private RendererInterface $renderer;
    private ServerRequest $serverRequest;
    private ?Category $category;
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;

    public function __construct(
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer,
        ServerRequest $serverRequest
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        $this->serverRequest = $serverRequest;

        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
        $this->category = $this->categoryRepository->find(
            $this->serverRequest->get('id', 0)
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
        $form->header('Подтвердите удаление!');
        $form->checkbox('remove_childs')->fill(['+ Удаление дочерних категорий']);
        $form->submit('delete', 'Удалить')->addClass('btn btn-danger');
        return $form;
    }

    private function doAction()
    {



        if ($this->serverRequest->post('remove_childs') !== null) {
            $allCategoryIds = $this->entityManager->getRepository(Category::class)->getAllIds($this->category);
            $products = $this->entityManager->getRepository(Product::class)->findByCategorysIds($allCategoryIds);
            $this->setNullCategory($products);

            $this->entityManager->remove($this->category);
            $this->entityManager->flush();
        } else {
            $products = $this->entityManager->getRepository(Product::class)->findByCategory($this->category);
            $this->setNullCategory($products);

            $this->categoryRepository->removeFromTree($this->category);
            $this->categoryRepository->updateLevelValues();
            $this->entityManager->clear();
        }
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }

    private function setNullCategory($products)
    {
        foreach ($products as $product) {
            $product->setCategory(null);
        }
        $this->entityManager->flush();
    }
}