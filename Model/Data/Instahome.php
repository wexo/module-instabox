<?php

namespace Wexo\Instabox\Model\Data;

use Magento\Framework\DataObject;
use Wexo\Instabox\Api\Data\InstahomeInterface;

class Instahome extends DataObject implements InstahomeInterface
{
    /**
     * @inheridoc
     */
    public function getNumber()
    {
        return $this->getData(static::NUMBER);
    }

    /**
     * @inheridoc
     */
    public function setNumber(string $string): InstahomeInterface
    {
        return $this->setData(static::NUMBER, $string);
    }

    /**
     * @inheridoc
     */
    public function getDescription()
    {
        return $this->getData(static::DESCRIPTION);
    }

    /**
     * @inheridoc
     */
    public function setDescription(string $string): InstahomeInterface
    {
        return $this->setData(static::DESCRIPTION, $string);
    }

    /**
     * @inheridoc
     */
    public function getCutoffDatetimeUtc()
    {
        return $this->getData(static::CUTOFF_DATETIME_UTC);
    }

    /**
     * @inheridoc
     */
    public function setCutoffDatetimeUtc(string $string): InstahomeInterface
    {
        return $this->setData(static::CUTOFF_DATETIME_UTC, $string);
    }

    /**
     * @inheridoc
     */
    public function getDatetimeUtc()
    {
        return $this->getData(static::DATETIME_UTC);
    }

    /**
     * @inheridoc
     */
    public function setDatetimeUtc(string $string): InstahomeInterface
    {
        return $this->setData(static::DATETIME_UTC, $string);
    }

    /**
     * @inheridoc
     */
    public function getEarliestPossibleDelivery()
    {
        return $this->getData(static::EARLIEST_POSSIBLE_DELIVERY);
    }

    /**
     * @inheridoc
     */
    public function setEarliestPossibleDelivery(string $string): InstahomeInterface
    {
        return $this->setData(static::EARLIEST_POSSIBLE_DELIVERY, $string);
    }

    /**
     * @inheridoc
     */
    public function getLastPossibleDelivery()
    {
        return $this->getData(static::LAST_POSSIBLE_DELIVERY);
    }

    /**
     * @inheridoc
     */
    public function setLastPossibleDelivery(string $string): InstahomeInterface
    {
        return $this->setData(static::LAST_POSSIBLE_DELIVERY, $string);
    }

    /**
     * @inheridoc
     */
    public function getDatetimeLocal()
    {
        return $this->getData(static::DATETIME_LOCAL);
    }

    /**
     * @inheridoc
     */
    public function setDatetimeLocal(string $string): InstahomeInterface
    {
        return $this->setData(static::DATETIME_LOCAL, $string);
    }

    /**
     * @inheridoc
     */
    public function getTextLocal()
    {
        return $this->getData(static::TEXT_LOCAL);
    }

    /**
     * @inheridoc
     */
    public function setTextLocal(string $string): InstahomeInterface
    {
        return $this->setData(static::TEXT_LOCAL, $string);
    }
}
