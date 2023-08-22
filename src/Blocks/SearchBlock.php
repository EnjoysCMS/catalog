<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Enjoys\Forms\Elements\Search;
use Enjoys\Forms\Elements\Submit;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Annotation\Block;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Block('Каталог. Поиск (форма)', [
    'template' => [
        'value' => '@m/catalog/blocks/search.twig',
        'name' => 'Путь до template',
        'description' => 'Обязательно',
    ],
])]
final class SearchBlock extends AbstractBlock
{

    public function __construct(
        private readonly Environment $twig,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }


    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(): string
    {
        $form = $this->getForm();
        $this->renderer->setForm($form);
        return $this->twig->render(
            $this->getOption('template'),
            [
                'form' => $form,
                'formRenderer' => $this->renderer,
                'blockOptions' => $this->getOptions()
            ]
        );
    }

    private function getForm(): Form
    {
        $form = new Form('get', $this->urlGenerator->generate('catalog/search'));
        $form->group()->add([
            (new Search('q'))->addRule(Rules::LENGTH, ['>=' => 3]),
            new Submit('search', 'Искать')
        ]);
        return $form;
    }
}
