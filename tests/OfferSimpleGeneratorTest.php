<?php

/*
 * This file is part of the Bukashk0zzzYmlGenerator
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\YmlGenerator\Tests;

use Bukashk0zzz\YmlGenerator\Model\Offer\OfferSimple;
use Bukashk0zzz\YmlGenerator\Settings;

/**
 * Generator test
 */
class OfferSimpleGeneratorTest extends AbstractGeneratorTest
{
    /**
     * Test generate
     */
    public function testGenerate()
    {
        $this->offerType = 'Simple';
        $this->runGeneratorTest();
    }

    protected function createSettings()
    {
        return (new Settings())
            ->setOutputFile('offer-simple.xml')
            ->setEncoding('utf-8')
            ->setIndentString("\t");
    }

    /**
     * {@inheritdoc}
     */
    protected function createOffer()
    {
        return (new OfferSimple())
            ->setName($this->faker->name)
            ->setVendor($this->faker->company)
            ->setVendorCode(null)
            ->setPickup(true)
            ->setCount($this->faker->numberBetween(1, 9999))
            ->setDisabled($this->faker->boolean)
            ->setGroupId($this->faker->numberBetween())
            ->addPicture('http://example.com/example.jpeg')
            ->addBarcode($this->faker->ean13)
            ->setCategoriesId([1, 2, 3])
            ->setCategoryId(999)
        ;
    }
}
