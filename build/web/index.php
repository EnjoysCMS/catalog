<?php

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\NoResultException;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;


if (php_sapi_name() === 'cli-server') {
    if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|woff2|ttf|woff)$/', $_SERVER["REQUEST_URI"])) {
        return false;    // сервер возвращает файлы напрямую.
    }
}


/** @var ContainerInterface $container */

include __DIR__ . '/../bootstrap.php';


try {
    /** @var Router $router */
    $router = $container->get('Router');
    $match = $router->matchRequest($container->get('RequestSymfony'));
    $container->get(ServerRequestInterface::class)->addQuery($match);
    $route = $router->getRouteCollection()->get($match['_route']);

    $controller = $route->getDefault('_controller');


    if (is_array($controller)) {
        $controller = implode('::', $controller);
    }


    echo $container->call($controller);
} catch (Exception $error) {
    $emitter = new SapiEmitter();
    $response = new Response();


    $container->get(LoggerInterface::class)->error($error->getMessage(), $error->getTrace());


    switch (get_debug_type($error)) {
        case ResourceNotFoundException::class:
        case NoResultException::class:
            $response = $response->withStatus(404);
            $response->getBody()->write($error->getMessage());
            $emitter->emit($response);
            break;
        case TableNotFoundException::class:
        default:
            $response = $response->withStatus(500);
            $response->getBody()->write($error->getMessage());
            $emitter->emit($response);
            break;
    }
}

