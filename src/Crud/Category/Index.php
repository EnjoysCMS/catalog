<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index implements ModelInterface
{
    /**
     * @var EntityRepository|ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;



    public function __construct(private EntityManager $em, private ServerRequestWrapper $requestWrapper, private UrlGeneratorInterface $urlGenerator)
    {
        $this->categoryRepository = $this->em->getRepository(Category::class);
    }

    public function getContext(): array
    {
        $form = new Form(
            [
                'method' => 'post'
            ]
        );
        $form->hidden('nestable-output')->setAttribute('id', 'nestable-output');
        $form->submit('save', 'Сохранить');


        if ($form->isSubmitted()) {
            $this->_recursive(\json_decode($this->requestWrapper->getPostData('nestable-output')));
            $this->em->flush();
            Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
        }
        $renderer = new Bootstrap4();
        $renderer->setForm($form);


        return [
            'form' => $renderer->render(),
            'categories' => $this->categoryRepository->getChildNodes()
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
