<?php

declare(strict_types=1);

namespace CatalogTest\Entities;

use EnjoysCMS\Module\Catalog\Entities\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{

    /**
     * @dataProvider dataUrlsForGetSlug
     */
    public function testGetSlug($expect, $data)
    {
        $first = array_shift($data);

        $category = new Category();
        $category->setUrl($first);
        $category->setParent(null);

        foreach ($data as $url) {
            $subcategory = new Category();
            $subcategory->setUrl($url);
            $subcategory->setParent($category);
            $category = $subcategory;
        }
        $this->assertSame($expect, $category->getSlug());
    }

    public function dataUrlsForGetSlug()
    {
        return [
            ['root/sub1/sub2', ['root', 'sub1', 'sub2']],
            ['1/2/3/4/5/6/7/8/9/10', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']],
            ['1/1/1', ['1', '1', '1']],
        ];
    }
}
