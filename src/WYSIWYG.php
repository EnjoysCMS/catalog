<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use DI\FactoryInterface;
use EnjoysCMS\Core\Components\WYSIWYG\NullEditor;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG as Instance;
use Psr\Container\ContainerInterface;
use Twig\Environment;

final class WYSIWYG
{
    static function getInstance($editorName, ContainerInterface $container): Instance
    {
        $twig = $container->get(Environment::class);

        try {
            $wysiwyg = $container->get(FactoryInterface::class)->make(Instance::class, [
                'editor' => $container->get($editorName),
                'twig' => $twig
            ]);

        } catch (\Error) {
            $wysiwyg = new Instance(new NullEditor(), $twig);
        }
        return $wysiwyg;
    }
}