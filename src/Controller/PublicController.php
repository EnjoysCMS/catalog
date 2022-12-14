<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\TwigFunction;

abstract class PublicController extends BaseController
{
    protected Module $module;

    public function __construct(
        protected ServerRequestInterface $request,
        protected Environment $twig,
        protected Config $config,
        ResponseInterface $response = null
    ) {
        parent::__construct($response);

        $this->module = new Module(
            Utils::parseComposerJson(
                __DIR__ . '/../../composer.json'
            )
        );

        $this->twig->addGlobal('module', $this->module);
        $this->twig->addGlobal('config', $this->config);

        $this->twig->addFunction(
            new TwigFunction('callstatic', function ($class, $method, ...$args) {
                if (!class_exists($class)) {
                    throw new \Exception("Cannot call static method $method on Class $class: Invalid Class");
                }
                if (!method_exists($class, $method)) {
                    throw new \Exception("Cannot call static method $method on Class $class: Invalid method");
                }
                return forward_static_call_array([$class, $method], $args);
            })
        );
    }

}
