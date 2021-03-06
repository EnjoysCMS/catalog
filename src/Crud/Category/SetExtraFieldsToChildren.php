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
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
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


    /**
     * @param RendererInterface $renderer
     * @param EntityManager $entityManager
     * @param ServerRequestInterface $serverRequest
     * @param UrlGeneratorInterface $urlGenerator
     * @param ContainerInterface $container
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);

        $this->category = $this->categoryRepository->find(
            $this->serverRequest->get('id', 0)
        );
        if ($this->category === null) {
            Error::code(404);
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
            'title' => sprintf("?????????????????? extra fields ???? %s ??  ???????????????? ??????????????????", $this->category->getTitle()),
            'subtitle' => '?????????????????? extra fields',
            'form' => $this->renderer,
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setMethod('post');
        $form->checkbox('removeOldExtraFields')->fill(
            [1 => '?????????????? ?? ???????????????? ?????????????????? ?????? ?????????????????????????? extra fields ?? ???????????????? ??????????']
        );
        $form->submit('setExtraFields', '????????????????????');
        return $form;
    }

    private function doActionRecursive(ArrayCollection|PersistentCollection $collection)
    {
        $extraFields = $this->category->getExtraFields();

        /** @var Category $item */
        foreach ($collection as $item) {
            if ($item->getChildren()->count()) {
                $this->doActionRecursive($item->getChildren());
            }

//      if(in_array($item->getId(), [34, 45, 46])){

            if ($this->serverRequest->post('removeOldExtraFields', false)) {
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
