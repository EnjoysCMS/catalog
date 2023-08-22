<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Setting;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: 'admin/catalog/setting',
    name: 'catalog/admin/setting'
)]
final class SettingController extends AdminController
{

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws DependencyException
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotFoundException
     */
    public function __invoke(SettingForm $setting): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Настройки');

        $form = $setting->getForm();
        if ($form->isSubmitted()) {
            $setting->doAction();
            return   $this->redirect->toUrl();
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);


        return $this->response(
            $this->twig->render(
                $this->templatePath . '/setting.twig',
                [
                    'form' => $rendererForm->output(),
                ]
            )
        );
    }

}
