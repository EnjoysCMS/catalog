<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Entities;

use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{

    /**
     * @dataProvider dataUrlsForCheckSlugs
     */
    public function testCheckSlugs($expect, $path, $urls)
    {
        $slugs = array_reverse(explode('/', $path));

        $first = array_shift($urls);

        $category = new Category();
        $category->setUrl($first);
        $category->setParent(null);

        foreach ($urls as $url) {
            $subcategory = new Category();
            $subcategory->setUrl($url);
            $subcategory->setParent($category);
            $category = $subcategory;
        }


        $this->assertSame($expect, $category->checkSlugs($slugs));
    }

    public function dataUrlsForCheckSlugs()
    {
        return [
            [true, 'root/1/2', ['root', '1', '2']],
            [false, 'same/root/1/2', ['root', '1', '2']],
            [false, 'root/invalid/2', ['root', '1', '2']],
            [false, 'root/1/0/2', ['root', '1', '2']],
            [false, '', ['root', '1', '2']],
            [true, '1/1/1', ['1', '1', '1']],
        ];
    }


}
