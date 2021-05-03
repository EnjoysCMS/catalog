<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;


use App\Entities\Amperage;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequest;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{
    private EntityManager $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private RendererInterface $renderer;
    private ServerRequest $serverRequest;
    private ?Category $category;

    public function __construct(EntityManager $entityManager, UrlGeneratorInterface $urlGenerator, RendererInterface $renderer, ServerRequest $serverRequest)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        $this->serverRequest = $serverRequest;

        $this->category = $this->entityManager->getRepository(Category::class)->find(
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
        $form->submit('delete', 'Удалить')->addClass('btn btn-danger');
        return $form;
    }

    private function doAction()
    {
        $this->entityManager->remove($this->category);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}