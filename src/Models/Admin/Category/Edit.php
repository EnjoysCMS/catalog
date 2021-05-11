<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Edit implements ModelInterface
{
    private RendererInterface $renderer;
    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private UrlGeneratorInterface $urlGenerator;
    private ?Category $category;
    private Environment $twig;

    public function __construct(
        RendererInterface $renderer,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ) {
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;

        $this->category = $this->entityManager->getRepository(Category::class)->find(
            $this->serverRequest->get('id', 0)
        );
        if ($this->category === null) {
            Error::code(404);
        }
        $this->twig = $twig;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $wysiwyg = new WYSIWYG(new Summernote());
        $wysiwyg->setTwig($this->twig);


        return [
            'title' => $this->category->getTitle(),
            'subtitle' => 'Изменение категории',
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }


    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->setDefaults(
            [
                'title' => $this->category->getTitle(),
                'description' => $this->category->getDescription(),
            ]
        );
        $form->text('title', 'Наименование');
        $form->textarea('description', 'Описание');
        $form->submit('add');
        return $form;
    }

    private function doAction()
    {
        $this->category->setTitle($this->serverRequest->post('title'));
        $this->category->setDescription($this->serverRequest->post('description'));
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/category'));
    }
}
