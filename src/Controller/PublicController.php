<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Helpers\ProductOptions;
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
    }

}
