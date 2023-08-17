<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Tags;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function trim;

class TagsList
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }


    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);

        return [
            'product' => $this->product,
            'subtitle' => 'Управление тегами',
            'form' => $this->renderer->output(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('@catalog_admin') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер тегов: %s', $this->product->getName()),
            ],
        ];
    }


    /**
     * @throws ExceptionRule
     */
    protected function getForm(): Form
    {
        $form = new Form();

        $form->setMethod('post');

        $form->setDefaults([
            'tags' => implode(
                ',',
                array_map(function ($tag) {
                    return trim($tag->getName());
                }, $this->product->getTags()->toArray())
            )
        ]);

        $form->text('tags', 'Теги')->setDescription('Теги через запятую')->setAttributes(
            AttributeFactory::createFromArray(['placeholder' => ''])
        );

        $form->submit('submit1', 'Изменить');

        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function doAction(): void
    {
        $tags = array_map('trim', array_unique(explode(',', $this->request->getParsedBody()['tags'] ?? null)));
        $manageTags = new TagsManager($this->em);
        $this->product->clearTags();
        $this->product->addTagsFromArray($manageTags->getTagsFromArray($tags));
        $this->em->flush();

        $this->redirect->toUrl(emit: true);
    }


}
