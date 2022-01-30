<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Dto;


final class SearchDto
{
    public int $id;
    public string $title;
    public string $slug;
    public array $options = [];
}
