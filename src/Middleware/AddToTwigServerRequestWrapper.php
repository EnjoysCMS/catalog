<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Middleware;


use Enjoys\ServerRequestWrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final class AddToTwigServerRequestWrapper implements MiddlewareInterface
{
    public function __construct(private Environment $twig)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->twig->addGlobal('_request', new ServerRequestWrapper($request));
        return $handler->handle($request);
    }
}
