<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/catalog/product/options', '@catalog_product_options_')]
final class Options extends AdminController
{


    /**
     * @throws ORMException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        name: 'list',
        comment: 'Просмотр опций товара'
    )]
    public function manageOptions(ModelOptions\Manage $manage, Setting $setting): ResponseInterface
    {
        $form = $manage->getForm();

        if ($form->isSubmitted()) {
            $manage->doSave();
            return $this->redirect->toUrl();
        }

        $this->breadcrumbs->add('@catalog_products', 'Список продуктов')->setLastBreadcrumb(
            sprintf('Характеристики: %s', $manage->getProduct()->getName())
        );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/options/options.twig',
                [
                    'product' => $manage->getProduct(),
                    'form' => $form,
                    'delimiterOptions' => $setting->get('delimiterOptions', '|'),
                    'subtitle' => 'Параметры'
                ]
            )
        );
    }

    #[Route(
        path: '/fill-from-product',
        name: 'fill_from_product',
        options: [
            'comment' => '[ADMIN] Заполнение опций из другого продукта'
        ]
    )]
    public function fillFromProduct(): void
    {
        $this->container->get(ModelOptions\FillFromProduct::class)();
    }

    #[Route(
        path: '/fill-from-text',
        name: 'fill_from_text',
        options: [
            'comment' => '[ADMIN] Заполнение опций из текста'
        ]
    )]
    public function fillFromText(): void
    {
        $this->container->get(ModelOptions\FillFromText::class)();
    }


    /**
     * @throws NotSupported
     */
    #[Route(
        path: '/find-option-keys',
        name: 'find_option_keys',
        comment: '[JSON] Получение списка названий опций (поиск)'
    )]
    public function getOptionKeys(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->json(
            $entityManager->getRepository(OptionKey::class)->like('name', $request->getQueryParams()['query'])
        );
    }

    #[Route(
        path: '/find-option-values',
        name: 'find_option_values',
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
        return $this->jsonResponse(
            $entityManager->getRepository(OptionValue::class)->like(
                'value',
                $request->getQueryParams()['query'] ?? null,
                $key
            )
        );
    }
}
