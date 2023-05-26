<?php

namespace EnjoysCMS\Module\Catalog\Blocks;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Filter extends AbstractBlock
{

    private Environment $twig;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(private Container $container, Entity $block)
    {
        parent::__construct($block);
        $this->twig = $this->container->get(Environment::class);
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function view(): string
    {
        return $this->twig->render(
            empty($this->getOption('template')) ? '../modules/catalog/template/blocks/filter.twig' : $this->getOption(
                'template'
            ),
            [
                'blockOptions' => $this->getOptions()
            ]
        );
    }
}
