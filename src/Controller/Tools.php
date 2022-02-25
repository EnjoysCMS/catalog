<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Tools extends PublicController
{
    private ServerRequestInterface $serverRequest;
    private SapiEmitter $emitter;
    private ResponseInterface $response;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->serverRequest = $this->container->get(ServerRequestInterface::class);
        $this->emitter =$this->container->get(EmitterInterface::class);
        $this->response = $this->container->get(ResponseInterface::class);
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
    public function translit(): StreamInterface
    {
        $query = $this->serverRequest->post('query');
        $response = $this->response
            ->withHeader('content-type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
        ;

        $response
            ->getBody()
            ->write(
                URLify::slug((string)$query)
            )
        ;
        $this->emitter->emit($response, true);
        return $response->getBody();
    }

}
