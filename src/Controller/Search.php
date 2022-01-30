<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Module\Catalog\Dto\SearchDto;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use HttpSoft\Emitter\EmitterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Search
{
    private EmitterInterface $emitter;
    private ResponseInterface $response;
    private array $optionKeys;

    public function __construct(private ContainerInterface $container)
    {
        $this->emitter = $this->container->get(EmitterInterface::class);
        $this->response = $this->container->get(ResponseInterface::class);
        $this->optionKeys = explode(',', Setting::get('searchOptionField', ''));
    }

    #[Route(
        path: '/catalog/search.json',
        name: 'catalog/api/search'
    )]
    public function apiSearch()
    {
        $this->response = $this->response->withHeader('content-type', 'application/json');

        try {
            $result = $this->container->get(\EnjoysCMS\Module\Catalog\Actions\Search::class)->getSearchResult(
                $this->optionKeys
            );

            $this->response->getBody()->write(
                json_encode($this->convertResultToDTO($result))
            );
        } catch (\Exception $e) {
            $this->response->getBody()->write(
                json_encode(['error' => $e->getMessage()])
            );
        }
        $this->emitter->emit($this->response);
    }

    #[Route(
        path: '/catalog/search.php',
        name: 'catalog/search'
    )]
    public function search(
        Environment $twig,
        BreadcrumbsInterface $breadcrumbs,
        UrlGeneratorInterface $urlGenerator
    ): string {
        try {
            $result = $this->container->get(\EnjoysCMS\Module\Catalog\Actions\Search::class)->getSearchResult(
                $this->optionKeys
            );
        } catch (\Exception $e) {
            $result = [
                'error' => $e
            ];
        }

        $breadcrumbs->add($urlGenerator->generate('catalog/index'), 'Каталог');
        $breadcrumbs->add(null, 'Поиск');

        return $twig->render('@m/catalog/search.twig', [
            'result' => $result,
            'breadcrumbs' => $breadcrumbs->get()
        ]);
    }

    private function convertResultToDTO($result)
    {
        $optionKeys = $result['optionKeys'];
        $result['result'] = array_map(function ($item) use ($optionKeys) {
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
        }, iterator_to_array($result['result']->getIterator()));
        return $result;
    }
}
