<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use DI\FactoryInterface;
use Enjoys\Forms\Elements\Search;
use Enjoys\Forms\Elements\Submit;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SearchBlock extends AbstractBlock
{
    private Environment $twig;
    private string $templatePath;

    /**
     * @param ContainerInterface&FactoryInterface $container
     * @param Entity $block
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(private ContainerInterface $container, Entity $block)
    {
        parent::__construct($block);
        $this->twig = $this->container->get(Environment::class);
        $this->templatePath = (string)$this->getOption('template');
    }


    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(): string
    {
        $form = $this->getForm();
        /** @var RendererInterface $renderer */
        $renderer = $this->container->make(RendererInterface::class);
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
        $form = new Form('get', $this->container->get(UrlGeneratorInterface::class)->generate('catalog/search'));
        $form->group()->add([
            (new Search('q'))->addRule(Rules::LENGTH, null, ['>=' =>  3]),
            new Submit('search', 'Искать')
        ]);
        return $form;
    }
}
