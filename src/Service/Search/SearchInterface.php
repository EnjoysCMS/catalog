<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Service\Search;


interface SearchInterface
{
    public function setSearchQuery(string $searchQuery): void;

    public function setOptionKeys(array $optionKeys): void;

    public function getResult(int $offset, int $limit): SearchResult;

}
