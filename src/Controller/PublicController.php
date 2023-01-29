<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Module\Catalog\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

abstract class PublicController
{
    protected Module $module;

    public function __construct(
        protected ServerRequestInterface $request,
        protected Environment $twig,
        protected Config $config,
        protected ResponseInterface $response
    ) {
        $this->module = new Module(
            Utils::parseComposerJson(
                __DIR__ . '/../../composer.json'
            )
        );

        $this->twig->addGlobal('module', $this->module);
        $this->twig->addGlobal('config', $this->config);
    }

    private function writeBody(string $body): void
    {
        $this->response->getBody()->write($body);
    }

    protected function responseText(string $body = ''): ResponseInterface
    {
        $this->writeBody($body);
        return $this->response;
    }

    protected function responseJson($data): ResponseInterface
    {
        $this->response = $this->response->withHeader('content-type', 'application/json');
        $this->writeBody(json_encode($data));
        return $this->response;
    }

}
