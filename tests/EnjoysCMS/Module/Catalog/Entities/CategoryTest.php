<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Entities;

use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{

    /**
     * @dataProvider dataUrlsForCheckSlugs
     */
    public function testCheckSlugs($expect, $path)
    {
        $slugs = array_reverse(explode('/', $path));

        $parentRoot = new Category();
        $parentRoot->setUrl('root');

        $subParent = new Category();
        $subParent->setUrl('1');
        $subParent->setParent($parentRoot);

        $category = new Category();
        $category->setParent($subParent);
        $category->setUrl('2');

        $this->assertSame($expect, $category->checkSlugs($slugs));
    }

    public function dataUrlsForCheckSlugs()
    {
        return [
            [true, 'root/1/2'],
            [false, 'same/root/1/2'],
            [false, 'root/invalid/2'],
            [false, 'root/1/0/2'],
            [false, ''],
        ];
    }
}
