# YML (Yandex Market Language) file generator

[![Build Status](https://img.shields.io/scrutinizer/build/g/Bukashk0zzz/YmlGenerator.svg?style=flat-square)](https://travis-ci.org/Bukashk0zzz/YmlGenerator)
[![Code Coverage](https://img.shields.io/codecov/c/github/Bukashk0zzz/YmlGenerator.svg?style=flat-square)](https://codecov.io/github/Bukashk0zzz/YmlGenerator)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Bukashk0zzz/YmlGenerator.svg?style=flat-square)](https://scrutinizer-ci.com/g/Bukashk0zzz/YmlGenerator/?branch=master)
[![License](https://img.shields.io/packagist/l/Bukashk0zzz/yml-generator.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/yml-generator)
[![Latest Stable Version](https://img.shields.io/packagist/v/Bukashk0zzz/yml-generator.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/yml-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/Bukashk0zzz/yml-generator.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/yml-generator)

About
-----
[YML (Yandex Market Language)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml) generator.
Uses standard XMLWriter for generating YML file. 
Not required any other library you just need PHP 5.5.0 or >= version.

Generator supports this offer types:
- OfferCustom [(vendor.model)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#vendor-model)
- OfferBook [(book)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#book)
- OfferAudiobook [(audiobook)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#audiobook)
- OfferArtistTitle [(artist.title)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#artist-title)
- OfferTour [(tour)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#tour)
- OfferEventTicket [(event-ticket)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#event-ticket)
- OfferSimple [(empty)](https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#base)

Installation
------------
Run composer require

```bash
composer require overplex/yml-generator
```


Or add this to your `composer.json` file:

```json
"require": {
  "overplex/yml-generator": "~1.11.6",
}
```

Usage examples
-------------

```php
<?php

use Bukashk0zzz\YmlGenerator\Model\Offer\OfferParam;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferSimple;
use Bukashk0zzz\YmlGenerator\Model\Category;
use Bukashk0zzz\YmlGenerator\Model\Currency;
use Bukashk0zzz\YmlGenerator\Model\Delivery;
use Bukashk0zzz\YmlGenerator\Model\ShopInfo;
use Bukashk0zzz\YmlGenerator\Settings;
use Bukashk0zzz\YmlGenerator\Generator;
use Bukashk0zzz\YmlGenerator\Cdata;

// Create second (unbuffered) connection to database (only for Yii 2)
$unbufferedDb = new \yii\db\Connection([
    'dsn' => \Yii::$app->db->dsn,
    'charset' => \Yii::$app->db->charset,
    'username' => \Yii::$app->db->username,
    'password' => \Yii::$app->db->password,
    'tablePrefix' => \Yii::$app->db->tablePrefix,
]);
$unbufferedDb->open();
$unbufferedDb->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

// Writing header to yml.xml
$yml = new Generator((new Settings())
    ->setOutputFile('yml.xml')
    ->setEncoding('UTF-8')
);

// Writing ShopInfo (https://yandex.ru/support/partnermarket/elements/shop.html)
$yml->addShopInfo((new ShopInfo())
    ->setName('BestShop')
    ->setCompany('Best online seller Inc.')
    ->setUrl('http://www.best.seller.com/')
);

// Writing currencies (https://yandex.ru/support/partnermarket/elements/currencies.html)
$yml->startCurrencies();
$yml->addCurrency((new Currency())
    ->setId('USD')
    ->setRate(1)
);
$yml->addCurrency((new Currency())
    ->setId('RUR')
    ->setRate(1)
);
$yml->finishBlock();

// Writing categories (https://yandex.ru/support/partnermarket/elements/categories.html)
$yml->startCategories();
/** @var CategoryModel $category */
foreach ($this->getCategoriesQuery()->each(50, $unbufferedDb) as $category) {
    $item = new Category();
    $item->setId($category->id);
    $item->setName($this->formatText($category->name));

    if ($category->parent) {
        $item->setParentId($category->parent->id);
    }

    $yml->addCategory($item);
    gc_collect_cycles();
}
$yml->finishBlock();

// Writing offers (https://yandex.ru/support/partnermarket/offers.html)
$yml->startOffers();
/** @var ProductModel $product */
foreach ($this->getProductsQuery()->each(50, $unbufferedDb) as $product) {

    $offer = new OfferSimple();
    $offer->setId($product->id);
    $offer->setUrl($product->getViewAbsoluteUrl());
    $offer->setName($product->name);
    $offer->setPrice($product->getPrice());
    $offer->setOldPrice($product->getOldPrice());
    $offer->setAvailable($product->in_stock);
    $offer->setVendorCode($product->articul);
    $offer->setCurrencyId('RUR');
    $offer->setWeight($product->weight);
    $offer->setDimensions($product->depth, $product->width, $product->height);
    $offer->setDescription(new CData($this->formatDescription($product->text)));
    $offer->addPicture($product->getMainPhotoAbsoluteUrl());

    if (!empty($manufacturer)) {
        $offer->addCustomElement('manufacturer', $manufacturer);
    }

    // Характеристики

    foreach ($product->properties as $value) {
        $offer->addParam((new OfferParam)->setName($value->property->name)->setValue($value->value));
    }

    $yml->addOffer($offer);
    gc_collect_cycles();
}
$yml->finishBlock();

// Optional writing deliveries (https://yandex.ru/support/partnermarket/elements/delivery-options.xml)
$yml->startDeliveries();
$yml->addDelivery((new Delivery())
    ->setCost(2)
    ->setDays(1)
    ->setOrderBefore(14)
);
$yml->finishBlock();

$yml->finish();
$unbufferedDb->close();
```

OZON example
------------

```php
$yml = new Generator((new Settings())
    ->setOutputFile('ozon.xml')
    ->setEncoding('UTF-8')
    ->hideDtd()
);

$yml->startOffers();

$offer = new OfferSimple();
$offer->setId('articul');
$offer->setPrice(100);
$offer->setOldPrice(150);
$offer->setInStock(1000);
$offer->hideAvailable();
$yml->addOffer($offer);

$yml->finishBlock();

$yml->finish();
```

Copyright / License
-------------------

See [LICENSE](https://github.com/bukashk0zzz/YmlGenerator/blob/master/LICENSE)
