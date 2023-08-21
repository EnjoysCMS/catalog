<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\PriceGroup;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\PriceGroup;

final class DeletePriceGroupForm
{

    public function __construct(private readonly EntityManager $em)
    {
    }


    public function getForm(PriceGroup $priceGroup): Form
    {
        $form = new Form();
        $form->header(sprintf('Удалить категорию цен: <b>%s</b>', $priceGroup->getTitle()));
        $form->html(
            '<div class="alert alert-warning">
                        <strong>Внимание!</strong> При удалении ценовой группы, все цены у товаров тоже удалятся и не будут
                        подлежать восстановлению.
                    </div>'
        );
        $form->submit('delete', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(PriceGroup $priceGroup): void
    {
        $this->em->remove($priceGroup);
        $this->em->flush();
    }
}
