<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Symfony\Component\Routing\Annotation\Route;

final class Tools
{
    private ServerRequestInterface $serverRequest;
    private SapiEmitter $emitter;

    public function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
        $this->emitter = new SapiEmitter();
    }

    /**
     * @Route(
     *     name="tools/translit",
     *     path="tools/translit",
     *     options={
     *      "aclComment": "Tools - транлитерация"
     *     }
     * )
     */
    public function translit(): \Psr\Http\Message\StreamInterface
    {
        $query = $this->serverRequest->post('query');
        $response = (new Response())
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