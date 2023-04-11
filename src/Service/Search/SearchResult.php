<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Service\Search;


use EnjoysCMS\Module\Catalog\Entities\Product;

final class SearchResult
{
    private string $searchQuery = '';
    private int $countResult = 0;
    private array $optionKeys = [];
    /**
     * @var Product[]
     */
    private iterable $result = [];


    public function getSearchQuery(): string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(string $searchQuery): void
    {
        $this->searchQuery = $searchQuery;
    }

    public function getCountResult(): int
    {
        return $this->countResult;
    }

    public function setCountResult(int $countResult): void
    {
        $this->countResult = $countResult;
    }

    public function getOptionKeys(): array
    {
        return $this->optionKeys;
    }

    public function setOptionKeys(array $optionKeys): void
    {
        $this->optionKeys = $optionKeys;
    }


    public function getResult(): iterable
    {
        return $this->result;
    }

    public function setResult(iterable $result): void
    {
        $this->result = $result;
    }
}
