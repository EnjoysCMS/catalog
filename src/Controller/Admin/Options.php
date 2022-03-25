<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Module\Catalog\Crud\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
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
    public function manageOptions(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/product/options/options.twig',
            $this->getContext($this->container->get(ModelOptions\Manage::class))
        ));
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
        ServerRequestWrapper $requestWrapper
    ): ResponseInterface {
        return $this->responseJson(
            $entityManager->getRepository(OptionKey::class)->like('name', $requestWrapper->getQueryData('query'))
        );
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
        ServerRequestWrapper $requestWrapper
    ): ResponseInterface {
        $key = $entityManager->getRepository(OptionKey::class)->findOneBy(
            ['name' => $requestWrapper->getQueryData('option'), 'unit' => $requestWrapper->getQueryData('unit')]
        );
        return $this->responseJson(
            $entityManager->getRepository(OptionValue::class)->like('value', $requestWrapper->getQueryData('query'), $key)
        );
    }
}
