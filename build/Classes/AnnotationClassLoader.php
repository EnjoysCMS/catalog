<?php

declare(strict_types=1);


namespace Build\Classes;

use Symfony\Component\Routing\Loader\AnnotationClassLoader as BaseLoader;
use Symfony\Component\Routing\Route;

class AnnotationClassLoader extends BaseLoader
{
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
    {
        if ('__invoke' === $method->getName()) {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', [$class->getName(), $method->getName()]);
        }
    }
}