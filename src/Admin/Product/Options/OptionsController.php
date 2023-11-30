<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Options as ModelOptions;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws ORMException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws OptimisticLockException
     * @throws \EnjoysCMS\Core\Exception\NotFoundException
     */
    #[Route(
        path: '/edit/{key_id}',
        name: 'edit',
        requirements: [
            'key_id' => Requirement::DIGITS
        ],
        options: [
            'comment' => 'Редактировать параметры характеристики'
        ]
    )]
    public function editOption(EditOptions $editOptions): ResponseInterface
    {
        $optionKey = $this->container->get(EntityManagerInterface::class)->getRepository(OptionKey::class)->find(
            $this->request->getAttribute('key_id', 0)
        ) ?? throw new \EnjoysCMS\Core\Exception\NotFoundException();

        $isSave = false;
        $form = $editOptions->getForm($optionKey);
        if ($form->isSubmitted()) {
            $editOptions->doSave($optionKey);
            $this->redirect->toUrl(emit: true);
            $isSave = true;
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/options/edit_options.twig',
                [
                    'form' => $rendererForm,
                    'save' => $isSave
                ]
            )
        );
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
        $option = $request->getQueryParams()['option'] ?? null;
        $unit = $request->getQueryParams()['unit'] ?? null;
        $query = $request->getQueryParams()['query'] ?? null;
        $limit = $request->getQueryParams()['limit'] ?? null;
        $page = $request->getQueryParams()['page'] ?? null;

        $key = $entityManager->getRepository(OptionKey::class)->findOneBy(
            [
                'name' => !empty($option) ? $option : null,
                'unit' => !empty($unit) ? $unit : null
            ]
        );

        return $this->jsonResponse(
            $entityManager->getRepository(OptionValue::class)->like(
                'value',
                !empty($query) ? $query : null,
                $key,
                !empty($page) ? $page : 1,
                !empty($limit) ? $limit : null
            )
        );
    }
}
