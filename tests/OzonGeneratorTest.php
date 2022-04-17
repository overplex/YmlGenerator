<?php

namespace Bukashk0zzz\YmlGenerator\Tests;

use Bukashk0zzz\YmlGenerator\Generator;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferSimple;
use Bukashk0zzz\YmlGenerator\Settings;
use Faker\Factory as Faker;
use PHPUnit_Framework_TestCase;

/**
 * OZON Generator test
 * @package Bukashk0zzz\YmlGenerator\Tests
 * @author Vladislav Kharitonov <vl.haritonov@gmail.com>
 */
class OzonGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->faker = Faker::create();
    }

    /**
     * Test generation
     */
    public function testGenerate()
    {
        $yml = new Generator($this->createSettings());

        $yml->startOffers();

        foreach ($this->createOffers() as $offer) {
            $yml->addOffer($offer);
        }

        $yml->finishBlock();

        $yml->finish();
    }

    /**
     * @return Settings
     */
    private function createSettings()
    {
        return (new Settings())
            ->setOutputFile('ozon.xml')
            ->setEncoding('UTF-8')
            ->hideDtd();
    }

    /**
     * @return array
     */
    protected function createOffers()
    {
        $offers = [];

        for ($i = 0; $i < 2; $i++) {
            $offer = new OfferSimple();
            $offer->setId($this->faker->text(10));
            $offer->setPrice($this->faker->numberBetween(1, 9999));
            $offer->setOldPrice($this->faker->numberBetween(1, 9999));
            $offer->setMinPrice($this->faker->numberBetween(1, 9999));
            $offer->addOutlet($this->faker->name, $this->faker->numberBetween(1, 9999));
            $offer->addOutlet($this->faker->name, $this->faker->numberBetween(1, 9999));
            $offer->hideAvailable();
            $offers[] = $offer;
        }

        return $offers;
    }
}
