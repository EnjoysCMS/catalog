<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Psr\Container\ContainerInterface;

require_once __DIR__ . "/bootstrap.php";

/** @var ContainerInterface $container */
return ConsoleRunner::createHelperSet($container->get(EntityManager::class));