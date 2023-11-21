<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Helpers;

use Psr\Http\Message\UriInterface;

final class Uri
{
    public static function withoutQueryValues(UriInterface $uri, array $keys): UriInterface
    {
        $result = self::getFilteredQueryString($uri, $keys);
        return $uri->withQuery(http_build_query($result));
    }


    public static function withoutQueryValue(UriInterface $uri, string $key): UriInterface
    {
        $result = self::getFilteredQueryString($uri, [$key]);
        return $uri->withQuery(http_build_query($result));
    }

    private static function getFilteredQueryString(UriInterface $uri, array $keys): array
    {
        $current = $uri->getQuery();

        if ($current === '') {
            return [];
        }
        parse_str($current, $currentParsed);

        foreach ($keys as $key) {
            self::unsetArrayByStringPath($currentParsed, $key);
        }

        return $currentParsed;
    }

    /**
     * @link https://www.gangofcoders.net/solution/using-a-string-path-to-set-nested-array-data/ - Solution 3
     */
    private static function unsetArrayByStringPath(&$array, $parents): void
    {
        if (!is_array($parents)) {
            $parents = explode('[', str_replace(']', '', $parents));
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            self::unsetArrayByStringPath($array[$key], $parents);
        }
    }
}
