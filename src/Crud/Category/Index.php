<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index implements ModelInterface
{
    /**
     * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;


    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->categoryRepository = $this->em->getRepository(Category::class);
    }

    public function getContext(): array
    {
        $form = new Form();
        $form->hidden('nestable-output')->setAttribute(AttributeFactory::create('id', 'nestable-output'));
        $form->submit('save', 'Сохранить');


        if ($form->isSubmitted()) {
            $this->_recursive(\json_decode($this->request->getParsedBody()['nestable-output'] ?? ''));
            $this->em->flush();
            Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
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

    private function _recursive($data, Category|null $parent = null): void
    {
        foreach ($data as $key => $value) {
            /** @var Category $item */
            $item = $this->categoryRepository->find($value->id);
            $item->setParent($parent);
            $item->setSort($key);
            $this->em->persist($item);
            if (isset($value->children)) {
                $this->_recursive($value->children, $item);
            }
        }
    }

}
