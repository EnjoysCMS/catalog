<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Filters\FilterInterface;

class OptionFilter implements FilterInterface
{
    private OptionKey $optionKey;

    public function __construct(
        $optionKey,
        private EntityManager $em,
        private ?string $formType = null,
        private array $currentValues = []
    ) {
        $this->optionKey = $em->getRepository(OptionKey::class)->find($optionKey) ?? throw new \RuntimeException(
            'OptionKey Id not found'
        );
    }

    public function getTitle(): string
    {
        return $this->optionKey->__toString();
    }

    public function getPossibleValues(array $pids): array
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
                'pids' => $pids,
                'optionKey' => $this->optionKey
            ])
            ->getQuery()
            ->getResult();

        foreach ($values as $value) {
            $result[$value->getId()] = $value->getValue();
        }

        return $result;
    }

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere(sprintf(':values%s MEMBER OF p.options', $this->optionKey->getId()))
            ->setParameter('values' . $this->optionKey->getId(), $this->currentValues);
    }

    public function getFormName(): string
    {
        return sprintf('filter[option][%s]', $this->optionKey->getId());
    }

    public function getFormType(): string
    {
        return $this->formType ?? 'checkbox';
    }

    public function getFormDefaults(array $values): array
    {
        return [];
    }

    public function addFormElement(Form $form, $values): Form
    {
        switch ($this->getFormType()) {
            case 'checkbox':
                $form->checkbox(
                    sprintf('%s[]', $this->getFormName()),
                    $this->getTitle()
                )->fill($values);
                break;
            case 'select-multiply':
                $form->select(sprintf('%s[]', $this->getFormName()), $this->getTitle())
                    ->setMultiple()
                    ->fill($values);
                break;
            case 'select':
                $form->select(
                    sprintf('%s[]', $this->getFormName()),
                    $this->getTitle()
                )->fill($values);
                break;
            case 'radio':
                $form->radio(
                    sprintf('%s[]', $this->getFormName()),
                    $this->getTitle()
                )->fill($values);
                break;
            default:
                throw new \RuntimeException('FormType not support');
        }
        return $form;
    }
}
