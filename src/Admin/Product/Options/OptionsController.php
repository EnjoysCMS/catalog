<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @TODO need refactor
 */
#[Route('/admin/catalog/product/options', '@catalog_product_options_')]
final class OptionsController extends AdminController
{


    /**
     * @throws DependencyException
     * @throws NoResultException
     * @throws NotFoundException
     * @throws ORMException
     */


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

    /**
     * @throws NotFoundException
     * @throws ORMException
     * @throws DependencyException
     * @throws NoResultException
     */
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


    /**
     * @throws NotSupported
     */
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
