<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Filters\FilterInterface;
use EnjoysCMS\Module\Catalog\Filters\FilterParams;

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
        return $this->formType ?? 'checkbox';
    }

    public function getFormElement(Form $form, $values): Form
    {
         switch ($this->getFormType()) {
            case 'checkbox':
                $form->checkbox(
                    sprintf('%s[]', $this->getFormName()),
                    $this->__toString()
                )->fill($values);
                break;
            case 'select-multiply':
                $form->select(sprintf('%s[]', $this->getFormName()), $this->__toString())
                    ->setMultiple()
                    ->fill($values);
                break;
            case 'select':
                $form->select(
                    sprintf('%s[]', $this->getFormName()),
                    $this->__toString()
                )->fill($values);
                break;
            case 'radio':
                $form->radio(
                    sprintf('%s[]', $this->getFormName()),
                    $this->__toString()
                )->fill($values);
                break;
            default:
                throw new \RuntimeException('FormType not support');
        }
        return $form;
    }
}
