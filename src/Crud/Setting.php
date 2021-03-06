<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;

final class Setting implements ModelInterface
{

    private ObjectRepository|EntityRepository $setting;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private RendererInterface $renderer
    ) {
        $this->setting = $this->entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Setting::class);
    }

    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        return [
            'form' => $form->render($this->renderer)
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);




        $form->setDefaults(function (){
            $setting = $this->setting->findAll();
            $defaults = [];
            /** @var \EnjoysCMS\Module\Catalog\Entities\Setting $item */
            foreach ($setting as $item) {
                $defaults[$item->getKey()] = explode(',', $item->getValue());
            }
            return $defaults;
        });

        $form->number('minSearchChars', 'minSearchChars');

        $form->select(
            'searchOptionField',
            "Дополнительные поля"
        )
            ->setAttribute('id', 'searchOptionField')
            ->setDescription(
                'Дополнительные поля, которые можно отображать в списке продуктов.
                Берутся из параметров товара (опций)'
            )
            ->setMultiple()
            ->fill(function () {
                $value = $this->setting->findOneBy(['key' => 'searchOptionField'])?->getValue();
                if ($value === null) {
                    return [];
                }
                $optionKeys = $this->entityManager->getRepository(OptionKey::class)->findBy(
                    [
                        'id' => explode(',', $value)
                    ]
                );
                $result = [];
                foreach ($optionKeys as $key) {
                    $result[$key->getId()] = [
                        $key->getName() . (($key->getUnit()) ? ' (' . $key->getUnit() . ')' : ''),
                        ['id' => uniqid()]
                    ];
                }
                return $result;
            });
        $form->submit('save', 'Сохранить');
        return $form;
    }

    private function doAction()
    {
        foreach ($this->setting->findAll() as $item) {
            $this->entityManager->remove($item);
        }
        $this->entityManager->flush();

        foreach ($this->serverRequest->post() as $key => $value) {
            switch ($key) {
                case 'minSearchChars':
                case 'searchOptionField':
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $setting = $this->setting->findOneBy(['key' => $key]);
                    if ($setting === null) {
                        $setting = new \EnjoysCMS\Module\Catalog\Entities\Setting();
                    }
                    $setting->setKey($key);
                    $setting->setValue($value);
                    $this->entityManager->persist($setting);

                    break;
            }
        }
        $this->entityManager->flush();
        Redirect::http();
    }
}
