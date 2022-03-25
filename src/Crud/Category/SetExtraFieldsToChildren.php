<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SetExtraFieldsToChildren implements ModelInterface
{

    private ?Category $category;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $categoryRepository;
    private ModuleConfig $config;


    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->requestWrapper->getQueryData('id', 0)
        );
        if ($this->category === null) {
            throw new NotFoundException(
                sprintf('Not found by id: %s', $this->requestWrapper->getQueryData('id', 0))
            );
        }

        $this->config = Config::getConfig($this->container);
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

    private function doActionRecursive(ArrayCollection|PersistentCollection $collection): void
    {
        $extraFields = $this->category->getExtraFields();

        /** @var Category $item */
        foreach ($collection as $item) {
            if ($item->getChildren()->count()) {
                $this->doActionRecursive($item->getChildren());
            }

//      if(in_array($item->getId(), [34, 45, 46])){

            if ($this->requestWrapper->getPostData('removeOldExtraFields', false)) {
                $item->removeExtraFields();
            }
            foreach ($extraFields->toArray() as $optionKey) {
                $item->addExtraField($optionKey);
            }

            $this->entityManager->persist($item);
            $this->entityManager->flush();
//      }
        }
    }
}
