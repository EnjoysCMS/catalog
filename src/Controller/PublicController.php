<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;

abstract class PublicController
{
    protected Module $module;
    protected Environment $twig;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected ContainerInterface $container)
    {
        $this->module = new Module(Utils::parseComposerJson(
            __DIR__ . '/../../composer.json'
        ));
        $this->twig = $this->container->get(Environment::class);
        $this->twig->addGlobal('module', $this->module);

    }
}
