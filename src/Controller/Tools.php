<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;


final class Tools extends PublicController
{

    /**
     * @deprecated remove in 5.0
     */
    #[Route(
        path: 'tools/translit',
        name: 'tools/translit',
        options: [
            'comment' => 'Tools - транлитерация'
        ]
    )]
    public function translit(): ResponseInterface
    {
        $query = $this->request->getParsedBody()['query'] ?? null;
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
        ;
        return $this->responseJson(URLify::slug((string)$query));

    }

}
