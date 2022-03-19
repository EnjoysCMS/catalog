<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use HttpSoft\Emitter\EmitterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Tools extends PublicController
{
    private ServerRequestInterface $serverRequest;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->serverRequest = $this->container->get(ServerRequestInterface::class);
    }

    /**
     * @Route(
     *     name="tools/translit",
     *     path="tools/translit",
     *     options={
     *      "comment": "Tools - транлитерация"
     *     }
     * )
     */
    public function translit(EmitterInterface $emitter): ResponseInterface
    {
        $query = $this->serverRequest->post('query');
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
        ;
        return $this->responseJson(URLify::slug((string)$query));

    }

}
