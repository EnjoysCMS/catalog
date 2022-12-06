<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Psr\Http\Message\ResponseInterface;


final class Tools extends PublicController
{

    /**
     * @Route(
     *     name="tools/translit",
     *     path="tools/translit",
     *     options={
     *      "comment": "Tools - транлитерация"
     *     }
     * )
     * @deprecated
     */
    public function translit(): ResponseInterface
    {
        $query = $this->request->getParsedBody()['query'] ?? null;
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
        ;
        return $this->responseJson(URLify::slug((string)$query));

    }

}
