<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Enjoys\Forms\Elements\Search;
use Enjoys\Forms\Elements\Submit;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SearchBlock extends AbstractBlock
{
    private Environment $twig;
    private string $templatePath;

    public function __construct(ContainerInterface $container, Entity $block)
    {
        parent::__construct($container, $block);
        $this->twig = $this->container->get(Environment::class);
        $this->templatePath = (string)$this->getOption('template');
    }


    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @return string
     */
    public function view()
    {
        $form = $this->getForm();
        /** @var Bootstrap4 $renderer */
        $renderer = $this->container->get(Bootstrap4::class);
        $renderer->setForm($form);
        return $this->twig->render(
            $this->templatePath,
            [
                'form' => $form,
                'formRenderer' => $renderer,
                'blockOptions' => $this->getOptions()
            ]
        );
    }

    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'get',
                'action' => $this->container->get(UrlGeneratorInterface::class)->generate('catalog/search')
            ]
        );
        $form->group()->add([
            (new Search('q'))->addRule(Rules::LENGTH, null, ['>=' =>  3]),
            new Submit('search', 'Искать')
        ]);
        return $form;
    }
}
