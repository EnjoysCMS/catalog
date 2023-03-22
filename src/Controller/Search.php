<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Dto\SearchDto;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Search extends PublicController
{
    private array $optionKeys;


    public function __construct(
        ServerRequestInterface $request,
        Environment $twig,
        Config $config,
        Setting $setting,
        ResponseInterface $response
    ) {
        parent::__construct($request, $twig, $config, $response);
        $this->optionKeys = explode(',', $setting->get('searchOptionField', ''));
    }


    #[Route(
        path: '/api/search/',
        name: 'catalog/api/search'
    )]
    public function apiSearch(ContainerInterface $container): ResponseInterface
    {
        try {
            $result = $container->get(\EnjoysCMS\Module\Catalog\Actions\Search::class)->getSearchResult(
                $this->optionKeys
            );
            $response = $this->responseJson($this->convertResultToDTO($result));
        } catch (Exception|Throwable $e) {
            $response = $this->responseJson(['error' => $e->getMessage()]);
        } finally {
            return $response;
        }
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route(
        path: '/search/',
        name: 'catalog/search'
    )]
    public function search(
        \EnjoysCMS\Module\Catalog\Actions\Search $search,
        BreadcrumbsInterface $breadcrumbs,
        UrlGeneratorInterface $urlGenerator
    ): ResponseInterface {
        try {
            $result = $search->getSearchResult(
                $this->optionKeys
            );
        } catch (Throwable $e) {
            $result = [
                'error' => $e
            ];
        }

        $breadcrumbs->add($urlGenerator->generate('catalog/index'), 'Каталог');
        $breadcrumbs->add(null, 'Поиск');

        $template_path = '@m/catalog/search.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/search.twig';
        }

        return $this->responseText(
            $this->twig->render($template_path, [
                'result' => $result,
                '_title' => $result['_title'],
                'breadcrumbs' => $breadcrumbs->get()
            ])
        );
    }

    private function convertResultToDTO($result)
    {
        $optionKeys = $result['optionKeys'];
        $result['result'] = array_map(
        /**
         * @throws Exception
         */ function ($item) use ($optionKeys) {
            /** @var \EnjoysCMS\Module\Catalog\Entities\Product $item */
            $searchDto = new SearchDto();
            $searchDto->id = $item->getId();
            $searchDto->title = $item->getName();
            $searchDto->slug = $item->getSlug();

            foreach ($item->getOptions() as $option) {
                if (!in_array($option['key']->getId(), $optionKeys)) {
                    continue;
                }
                $searchDto->options[$option['key']->getName()] = array_map(function ($item) {
                    return $item->getValue();
                }, $option['values']);
            }

            return $searchDto;
        },
            iterator_to_array($result['products']->getIterator())
        );
        return $result;
    }
}
