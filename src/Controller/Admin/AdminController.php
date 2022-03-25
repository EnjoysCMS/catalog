<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Container\ContainerInterface;

abstract class AdminController extends BaseController
{
    protected Module $module;
    protected string $templatePath;


    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct($container);
        $this->templatePath = Config::getAdminTemplatePath($container);
        $this->module = new Module(
            Utils::parseComposerJson(
                __DIR__ . '/../../../composer.json'
            )
        );
        $this->getTwig()->getLoader()->addPath($this->templatePath, 'catalog_admin');
        $this->getTwig()->addGlobal('module', $this->module);
    }

}
