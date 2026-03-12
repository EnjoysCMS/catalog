<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Brands;


use DI\DependencyException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Repository\VendorRepository;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


#[Route('admin/catalog/brand', '@catalog_brand_')]
final class Controller extends AdminController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: 's',
        name: 'list',
        comment: 'Список брендов'
    )]
    public function list(VendorRepository $vendorRepository): ResponseInterface
    {
        return $this->response(
            $this->twig->render($this->templatePath . '/brands.twig', [
                'brands' => $vendorRepository->findAll(),
            ]),
        );
    }

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     * @throws SyntaxError
     * @throws \DI\NotFoundException
     * @throws NotSupported
     * @throws NotFoundException
     */
    #[Route(
        path: '/{vendor_id}/edit',
        name: 'edit',
        requirements: [
            'vendor_id' => Requirement::UUID,
        ],
        comment: 'Редактирование бренда'
    )]
    public function edit(UpdateVendorForm $edit, VendorRepository $vendorRepository): ResponseInterface
    {
        $vendor = $vendorRepository->find($this->request->getAttribute('vendor_id')) ?? throw new NotFoundException();


        $this->breadcrumbs->setLastBreadcrumb(
            sprintf("Редактирование бренда [%s]", $vendor->getName()),
        );

        $form = $edit->getForm($vendor);

        if ($form->isSubmitted()) {
            $edit->doAction($vendor);
            return $this->redirect->toRoute('@catalog_brand_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editbrand.twig',
                [
                    'title' => $vendor->getName(),
                    'subtitle' => 'Изменение бренда',
                    'form' => $rendererForm,
                ],
            ),
        );
    }

}
