<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin;


use DI\Container;
use EnjoysCMS\Core\Extensions\Composer\Utils;
use EnjoysCMS\Core\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;

abstract class AdminController extends \EnjoysCMS\Module\Admin\AdminController
{
    protected Module $module;
    protected string $templatePath;


    public function __construct(
        protected Container $container,
        protected Config $config,
        protected \EnjoysCMS\Module\Admin\Config $adminConfig
    ) {
        parent::__construct($container);
        $this->templatePath = $this->config->getAdminTemplatePath();
        $this->module = new Module(
            Utils::parseComposerJson(
                __DIR__ . '/../../composer.json'
            )
        );
        $this->twig->getLoader()->addPath($this->templatePath, 'catalog_admin');
        $this->twig->addGlobal('module', $this->module);
        $this->twig->addGlobal('config', $this->config);

        $this->breadcrumbs->add('@catalog_admin', 'Каталог');
    }

}
