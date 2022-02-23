<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Crud\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Options extends AdminController
{


    /**
     * @Route(
     *     path="admin/catalog/product/options",
     *     name="@a/catalog/product/options",
     *     options={
     *      "aclComment": "Просмотр опций товара"
     *     }
     * )
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function manageOptions(): string
    {
        return $this->view(
            $this->templatePath . '/product/options/options.twig',
            $this->getContext($this->container->get(ModelOptions\Manage::class))
        );
    }

    /**
     * @Route (
     *     path="admin/catalog/product/options/fill-from-product",
     *     name="@a/catalog/product/options/fill-from-product",
     *     options={
     *      "aclComment": "[ADMIN] Заполнение опций из другого продукта"
     *     }
     * )
     */
    public function fillFromProduct(): void
    {
        $this->container->get(ModelOptions\FillFromProduct::class)();
    }

    /**
     * @Route (
     *     path="admin/catalog/product/options/fill-from-text",
     *     name="@a/catalog/product/options/fill-from-text",
     *     options={
     *      "aclComment": "[ADMIN] Заполнение опций из текста"
     *     }
     * )
     */
    public function fillFromText(): void
    {
        $this->container->get(ModelOptions\FillFromText::class)();
    }


    /**
     * @Route (
     *     path="admin/catalog/tools/find-option-keys",
     *     name="@a/catalog/tools/find-option-keys",
     *     options={
     *      "aclComment": "[JSON] Получение списка названий опций (поиск)"
     *     }
     * )
     */
    public function getOptionKeys(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        Response $response,
        SapiEmitter $emitter
    ): void {
        $matched = $entityManager->getRepository(OptionKey::class)->like('name', $serverRequest->get('query'));
        $response = $response->withHeader('content-type', 'application/json');
        $response->getBody()->write(json_encode($matched));
        $emitter->emit($response);
    }

    /**
     * @Route (
     *     path="admin/catalog/tools/find-option-values",
     *     name="@a/catalog/tools/find-option-values",
     *     options={
     *      "aclComment": "[JSON] Получение списка значений опций (поиск)"
     *     }
     * )
     */
    public function getOptionValues(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        Response $response,
        SapiEmitter $emitter
    ): void {
        $key = $entityManager->getRepository(OptionKey::class)->findOneBy(
            ['name' => $serverRequest->get('option'), 'unit' => $serverRequest->get('unit')]
        );
        $matched = $entityManager->getRepository(OptionValue::class)->like('value', $serverRequest->get('query'), $key);
        $response = $response->withHeader('content-type', 'application/json');
        $response->getBody()->write(json_encode($matched));
        $emitter->emit($response);
    }
}
