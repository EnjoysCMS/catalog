<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Form;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\Product;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteUrlProductForm
{
    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {

    }



    public function getForm(Product $product): Form
    {
        $url = $product->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? null));


        $form = new Form();
        $form->header(sprintf('Удалить ссылку: %s?', $url->getPath()));
        $form->submit('save', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(Product $product): void
    {
        $url = $product->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? null));


        if ($url->isDefault()) {
            throw new InvalidArgumentException('You cannot delete the main link');
        }
        $this->em->remove($url);
        $this->em->flush();
    }
}
