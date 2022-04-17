<?php

/*
 * This file is part of the Bukashk0zzzYmlGenerator
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\YmlGenerator;

use Bukashk0zzz\YmlGenerator\Model\Category;
use Bukashk0zzz\YmlGenerator\Model\Currency;
use Bukashk0zzz\YmlGenerator\Model\Delivery;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferCondition;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferGroupAwareInterface;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferInterface;
use Bukashk0zzz\YmlGenerator\Model\Offer\OfferParam;
use Bukashk0zzz\YmlGenerator\Model\ShopInfo;

/**
 * Class Generator
 */
class Generator
{
    /**
     * @var string
     */
    protected $tmpFile;

    /**
     * @var \XMLWriter
     */
    protected $writer;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * Generator constructor.
     *
     * @param Settings $settings
     */
    public function __construct($settings = null)
    {
        $this->settings = $settings instanceof Settings ? $settings : new Settings();
        $this->writer = new \XMLWriter();

        if ($this->settings->getOutputFile() !== null && $this->settings->getReturnResultYMLString()) {
            throw new \LogicException(
                'Only one destination need to be used ReturnResultYMLString or OutputFile'
            );
        }

        if ($this->settings->getReturnResultYMLString()) {
            $this->writer->openMemory();
        } else {
            $this->tmpFile = $this->settings->getOutputFile() !== null
                ? \tempnam(\sys_get_temp_dir(), 'YMLGenerator')
                : 'php://output';
            $this->writer->openURI($this->tmpFile);
        }

        if ($this->settings->getIndentString()) {
            $this->writer->setIndentString($this->settings->getIndentString());
            $this->writer->setIndent(true);
        }

        try {
            $this->addHeader();
        } catch (\Exception $exception) {
            $this->throwException($exception);
        }
    }

    public function finish()
    {
        try {
            $this->addFooter();

            if ($this->settings->getReturnResultYMLString()) {
                return $this->writer->flush();
            }

            if ($this->settings->getOutputFile() !== null) {
                \copy($this->tmpFile, $this->settings->getOutputFile());
                @\unlink($this->tmpFile);
            }

            return true;

        } catch (\Exception $exception) {
            $this->throwException($exception);
        }

        return false;
    }

    /**
     * Adds shop element data. (See https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#shop)
     *
     * @param ShopInfo $shopInfo
     */
    public function addShopInfo(ShopInfo $shopInfo)
    {
        try {
            foreach ($shopInfo->toArray() as $name => $value) {
                if ($value !== null) {
                    $this->writer->writeElement($name, $value);
                }
            }
        } catch (\Exception $exception) {
            $this->throwException($exception);
        }
    }

    /**
     * @param Currency $currency
     */
    public function addCurrency(Currency $currency)
    {
        $this->writer->startElement('currency');
        $this->writer->writeAttribute('id', $currency->getId());
        $this->writer->writeAttribute('rate', $currency->getRate());
        $this->writer->endElement();
    }

    /**
     * @param Category $category
     */
    public function addCategory(Category $category)
    {
        $this->writer->startElement('category');
        $this->writer->writeAttribute('id', $category->getId());

        if ($category->getParentId() !== null) {
            $this->writer->writeAttribute('parentId', $category->getParentId());
        }

        if (!empty($category->getAttributes())) {
            foreach ($category->getAttributes() as $attribute) {
                $this->writer->writeAttribute($attribute['name'], $attribute['value']);
            }
        }

        $this->writer->text($category->getName());
        $this->writer->fullEndElement();
    }

    /**
     * @param Delivery $delivery
     */
    public function addDelivery(Delivery $delivery)
    {
        $this->writer->startElement('option');
        $this->writer->writeAttribute('cost', $delivery->getCost());
        $this->writer->writeAttribute('days', $delivery->getDays());
        if ($delivery->getOrderBefore() !== null) {
            $this->writer->writeAttribute('order-before', $delivery->getOrderBefore());
        }
        $this->writer->endElement();
    }

    /**
     * @param OfferInterface $offer
     */
    public function addOffer(OfferInterface $offer)
    {
        $this->writer->startElement('offer');
        $this->writer->writeAttribute('id', $offer->getId());

        if ($offer->getShowAvailable()) {
            $this->writer->writeAttribute('available', $offer->isAvailable() ? 'true' : 'false');
        }

        if ($offer->getType() !== null) {
            $this->writer->writeAttribute('type', $offer->getType());
        }

        if ($offer instanceof OfferGroupAwareInterface && $offer->getGroupId() !== null) {
            $this->writer->writeAttribute('group_id', $offer->getGroupId());
        }

        foreach ($offer->toArray() as $name => $value) {
            if (\is_array($value)) {
                if ($name === 'outlets') {
                    if (count($value) > 0) {
                        $this->addOutlets($value);
                    }
                } else {
                    foreach ($value as $itemValue) {
                        $this->addOfferElement($name, $itemValue);
                    }
                }
            } else {
                $this->addOfferElement($name, $value);
            }
        }
        $this->addOfferParams($offer);
        $this->addOfferDeliveryOptions($offer);
        $this->addOfferCondition($offer);

        $this->writer->fullEndElement();
    }

    /**
     * Adds <currencies> element.
     * @see https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#currencies
     */
    public function startCurrencies()
    {
        $this->writer->startElement('currencies');
    }

    /**
     * Adds <categories> element.
     * @see https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#categories
     */
    public function startCategories()
    {
        $this->writer->startElement('categories');
    }

    /**
     * Adds <delivery-option> element.
     * @see https://yandex.ru/support/partnermarket/elements/delivery-options.xml
     */
    public function startDeliveries()
    {
        $this->writer->startElement('delivery-options');
    }

    /**
     * Adds <offers> element.
     * @see https://yandex.ru/support/webmaster/goods-prices/technical-requirements.xml#offers
     */
    public function startOffers()
    {
        $this->writer->startElement('offers');
    }

    /**
     * Add close tag
     */
    public function finishBlock()
    {
        $this->writer->fullEndElement();
    }

    protected function addOutlets($outlets)
    {
        $this->writer->startElement('outlets');

        foreach ($outlets as $name => $inStock) {
            $this->addOutlet($name, $inStock);
        }

        $this->writer->fullEndElement();
    }

    protected function addOutlet($name, $inStock)
    {
        $this->writer->startElement('outlet');
        $this->writer->writeAttribute('instock', $inStock);

        if (!empty($name)) {
            $this->writer->writeAttribute('warehouse_name', $name);
        }

        $this->writer->fullEndElement();
    }

    /**
     * Add document header
     */
    protected function addHeader()
    {
        $this->writer->startDocument('1.0', $this->settings->getEncoding());

        if ($this->settings->getAddDtd()) {
            $this->writer->startDTD('yml_catalog', null, 'shops.dtd');
            $this->writer->endDTD();
        }

        $this->writer->startElement('yml_catalog');
        $this->writer->writeAttribute('date', \date('Y-m-d H:i'));
        $this->writer->startElement('shop');
    }

    /**
     * Add document footer
     */
    protected function addFooter()
    {
        $this->writer->fullEndElement();
        $this->writer->fullEndElement();
        $this->writer->endDocument();
    }

    /**
     * @param OfferInterface $offer
     */
    private function addOfferDeliveryOptions(OfferInterface $offer)
    {
        $deliveries = $offer->getDeliveryOptions();

        if (!empty($deliveries)) {

            $this->startDeliveries();

            /** @var Delivery $delivery */
            foreach ($deliveries as $delivery) {
                if ($delivery instanceof Delivery) {
                    $this->addDelivery($delivery);
                }
            }

            $this->finishBlock();
        }
    }

    /**
     * @param OfferInterface $offer
     */
    private function addOfferParams(OfferInterface $offer)
    {
        /** @var OfferParam $param */
        foreach ($offer->getParams() as $param) {
            if ($param instanceof OfferParam) {
                $this->writer->startElement('param');

                $this->writer->writeAttribute('name', $param->getName());
                if ($param->getUnit()) {
                    $this->writer->writeAttribute('unit', $param->getUnit());
                }
                $this->writer->text($param->getValue());

                $this->writer->endElement();
            }
        }
    }

    /**
     * @param OfferInterface $offer
     */
    private function addOfferCondition(OfferInterface $offer)
    {
        $params = $offer->getCondition();
        if ($params instanceof OfferCondition) {
            $this->writer->startElement('condition');
            $this->writer->writeAttribute('type', $params->getType());
            $this->writer->writeElement('reason', $params->getReasonText());
            $this->writer->endElement();
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return bool
     */
    private function addOfferElement($name, $value)
    {
        if ($value === null) {
            return false;
        }

        if ($value instanceof Cdata) {
            $this->writer->startElement($name);
            $this->writer->writeCdata((string)$value);
            $this->writer->endElement();

            return true;
        }

        if (\is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->writer->writeElement($name, $value);

        return true;
    }

    private function throwException($exception)
    {
        throw new \RuntimeException(\sprintf('Problem with generating YML file: %s',
            $exception->getMessage()), 0, $exception);
    }
}
