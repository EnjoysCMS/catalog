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
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Range;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Select;
use EnjoysCMS\Module\Catalog\Service\Filters\FormType\Slider;

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
        if (in_array('max', array_keys($this->params->currentValues))
            || in_array('min', array_keys($this->params->currentValues))
        ) {
            $min = (empty($this->params->currentValues['min'])) ? null : $this->params->currentValues['min'];
            $max = (empty($this->params->currentValues['max'])) ? null : $this->params->currentValues['max'];

            $subSelect = $qb->getEntityManager()->createQueryBuilder()
                ->select('v.id')
                ->from(OptionValue::class, 'v')
                ->where('v.optionKey = :optionKey')
                ->setParameter('optionKey', $this->optionKey->getId());
            if ($min) {
                $subSelect->andWhere('v.value >=  :minValue')
                    ->setParameter('minValue', $min);
            }
            if ($max) {
                $subSelect->andWhere('v.value <=  :maxValue')
                    ->setParameter('maxValue', $max);
            }


            if ($min || $max) {
                return $qb->andWhere(':values MEMBER OF p.options')
                    ->setParameter('values', $subSelect->getQuery()->getResult());
            }
            return $qb;
        }

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
            case 'slider':
                (new Slider($form, $this, $values))->create();
                break;
            case 'range':
                (new Range($form, $this, $values))->create();
                break;
            default:
                throw new \RuntimeException('FormType not support');
        }
        return $form;
    }
}
