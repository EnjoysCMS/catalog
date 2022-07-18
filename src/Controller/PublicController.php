<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

abstract class PublicController extends BaseController
{
    protected Module $module;

    public function __construct(
        protected ServerRequestWrapper $request,
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
