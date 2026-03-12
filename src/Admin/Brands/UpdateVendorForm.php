<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Brands;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Enjoys\Forms\Elements\Html;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\Vendor;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateVendorForm
{

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
    ) {}


    public function getForm(Vendor $vendor): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
//                'name' => $vendor->getName(),
                'description' => $vendor->getDescription(),
                'logo' => $vendor->getLogo(),
                'sortWeight' => $vendor->getSortWeight(),
                // 'status' => [(int)($category?->isStatus() ?? true)],
            ],
        );

//        $form->checkbox('status')
//            ->addClass(
//                'custom-switch custom-switch-off-danger custom-switch-on-success',
//                Form::ATTRIBUTES_FILLABLE_BASE
//            )
//            ->fill([1 => 'Статус']);


        $form->header($vendor->getName());
        $form->textarea('description', 'Описание');
        $form->number('sortWeight', 'Вес сортировки');

        $form
            ->group('Изображение')
            ->add(
                [
                    new Text('logo'),
                    new Html(
                        <<<HTML
                            <a class="btn btn-default btn-outline btn-upload"  id="inputImage" title="Upload image file">
                                <span class="fa fa-upload "></span>
                            </a>
                            HTML,
                    ),
                ],
            );


        $form->submit('edit');
        return $form;
    }


    /**
     * @throws NotSupported
     * @throws ORMException
     */
    public function doAction(Vendor $vendor): Vendor
    {
        $vendor->setSortWeight((int)($this->request->getParsedBody()['sortWeight'] ?? 0));
        $vendor->setDescription($this->request->getParsedBody()['description'] ?? null);
        $vendor->setLogo($this->request->getParsedBody()['logo'] ?? null);

        $this->em->flush();

        return $vendor;
    }

}
