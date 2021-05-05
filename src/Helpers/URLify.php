<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


final class URLify
{
    static public function slug(
        string $string,
        int $maxLength = 200,
        string $separator = '-',
        string $language = 'en'
    ) {
        return \URLify::slug($string, $maxLength, $separator, $language);
    }

}