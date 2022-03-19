<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

abstract class PublicController extends  BaseController
{
    protected Module $module;
    protected Environment $twig;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct($this->container->get(ResponseInterface::class));
        $this->module = new Module(Utils::parseComposerJson(
            __DIR__ . '/../../composer.json'
        ));
        $this->twig = $this->container->get(Environment::class);
        $this->twig->addGlobal('module', $this->module);

    }
}
