<?php

use DI\Container;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Enjoys\Dotenv\Dotenv;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use Gedmo\Mapping\Annotation\Tree;
use Gedmo\Mapping\Annotation\TreeClosure;
use Gedmo\Mapping\Annotation\TreeLevel;
use Gedmo\Mapping\Annotation\TreeParent;
use Gedmo\Mapping\Annotation\Timestampable;

$loader = require_once __DIR__ . "/../vendor/autoload.php";

AnnotationRegistry::loadAnnotationClass(TreeLevel::class);
AnnotationRegistry::loadAnnotationClass(Tree::class);
AnnotationRegistry::loadAnnotationClass(TreeClosure::class);
AnnotationRegistry::loadAnnotationClass(TreeParent::class);
AnnotationRegistry::loadAnnotationClass(Timestampable::class);

try {
    $dotenv = new Dotenv(__DIR__);
    $dotenv->loadEnv();
} catch (Exception $e) {
    echo 'ENV Error: ' . $e->getMessage();
    exit;
}

//var_dump($_ENV['DB_DSN']);
/** @var Container $container */
$container = include __DIR__ . '/container.php';
HelpersBase::setContainer($container);