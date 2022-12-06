<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Crud\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Options extends AdminController
{


    /**
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/product/options',
        name: '@a/catalog/product/options',
        options: [
            'comment' => 'Просмотр опций товара'
        ]
    )]
    public function manageOptions(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                $this->templatePath . '/product/options/options.twig',
                $this->getContext($this->container->get(ModelOptions\Manage::class))
            )
        );
    }

    #[Route(
        path: 'admin/catalog/product/options/fill-from-product',
        name: '@a/catalog/product/options/fill-from-product',
        options: [
            'comment' => '[ADMIN] Заполнение опций из другого продукта'
        ]
    )]
    public function fillFromProduct(): void
    {
        $this->container->get(ModelOptions\FillFromProduct::class)();
    }

    #[Route(
        path: 'admin/catalog/product/options/fill-from-text',
        name: '@a/catalog/product/options/fill-from-text',
        options: [
            'comment' => '[ADMIN] Заполнение опций из текста'
        ]
    )]
    public function fillFromText(): void
    {
        $this->container->get(ModelOptions\FillFromText::class)();
    }


    #[Route(
        path: 'admin/catalog/tools/find-option-keys',
        name: '@a/catalog/tools/find-option-keys',
        options: [
            'comment' => '[JSON] Получение списка названий опций (поиск)'
        ]
    )]
    public function getOptionKeys(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->responseJson(
            $entityManager->getRepository(OptionKey::class)->like('name', $request->getQueryParams()['query'])
        );
    }

    #[Route(
        path: 'admin/catalog/tools/find-option-values',
        name: '@a/catalog/tools/find-option-values',
        options: [
            'comment' => '[JSON] Получение списка значений опций (поиск)'
        ]
    )]
    public function getOptionValues(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        $key = $entityManager->getRepository(OptionKey::class)->findOneBy(
            [
                'name' => $request->getQueryParams()['option'] ?? null,
                'unit' => $request->getQueryParams()['unit'] ?? null
            ]
        );
        return $this->responseJson(
            $entityManager->getRepository(OptionValue::class)->like(
                'value',
                $request->getQueryParams()['query'] ?? null,
                $key
            )
        );
    }
}
