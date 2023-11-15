<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterParams;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Checkbox;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Radio;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Select;

class OptionFilter implements FilterInterface
{
    private OptionKey $optionKey;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private FilterParams $params,
        private EntityManager $em
    ) {
        $this->optionKey = $em->getRepository(OptionKey::class)->find(
            $this->params->optionKey ?? 0
        ) ?? throw new \RuntimeException(
            'OptionKey Id not found'
        );
    }

    public function __toString(): string
    {
        return $this->optionKey->__toString();
    }

    public function getPossibleValues(array $productIds): array
    {
        $result = [];

        /** @var OptionValue[] $values */
        $values = $this->em
            ->createQueryBuilder()
            ->select('v')
            ->from(OptionValue::class, 'v')
            ->where('v.optionKey = :optionKey')
            ->andWhere(':pids MEMBER OF v.products')
            ->setParameters([
                'pids' => $productIds,
                'optionKey' => $this->optionKey
            ])
            ->getQuery()
            ->getResult();

        foreach ($values as $value) {
            $result[$value->getId()] = $value->getValue();
        }

        return $result;
    }

    public function addFilterQueryBuilderRestriction(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere(sprintf(':values%s MEMBER OF p.options', $this->optionKey->getId()))
            ->setParameter('values' . $this->optionKey->getId(), $this->params->currentValues ?? []);
    }

    public function getFormName(): string
    {
        return sprintf('filter[option][%s]', $this->optionKey->getId());
    }

    public function getFormType(): string
    {
        return $this->params->formType ?? 'checkbox';
    }

    public function getFormElement(Form $form, $values): Form
    {
        switch ($this->getFormType()) {
            case 'checkbox':
                (new Checkbox($form, $this, $values))->create();
                break;
            case 'select-multiply':
                (new Select($form, $this, $values))->multiple()->create();
                break;
            case 'select':
                (new Select($form, $this, $values))->create();
                break;
            case 'radio':
                (new Radio($form, $this, $values))->create();
                break;
//            case 'slider':
//
//
//                $min = min($values);
//                $max = max($values);
//
//
//                $form->group($this->__toString())
//                    ->addClass('slider-group')
//                    ->add([
//                        (new Number('filter[price][min]'))
//                            ->addClass('minInput')
//                            ->setMin($min)
//                            ->setMax($max),
//                        (new Number('filter[price][max]'))
//                            ->addClass('maxInput')
//                            ->setMin($min)
//                            ->setMax($max)
//                        ,
//                    ]);
//                break;
            default:
                throw new \RuntimeException('FormType not support');
        }
        return $form;
    }
}
