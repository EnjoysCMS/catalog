<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Service\Search;


interface SearchInterface
{
    public function getResult(): SearchResult;
    public function setOptionKeys(array $optionKeys): void;
}
