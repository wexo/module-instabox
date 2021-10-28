<?php

namespace Wexo\Instabox\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface InstahomeInterface extends ExtensibleDataInterface
{
    const NUMBER = 'number';
    const DESCRIPTION = 'description';
    const CUTOFF_DATETIME_UTC = 'cutoff_datetime_utc';
    const DATETIME_UTC = 'datetime_utc';
    const EARLIEST_POSSIBLE_DELIVERY = 'earliest_possible_delivery';
    const LAST_POSSIBLE_DELIVERY = 'last_possible_delivery';
    const DATETIME_LOCAL = 'datetime_local';
    const TEXT_LOCAL = 'text_local';

    /**
     * @return string|null
     */
    public function getNumber();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setNumber(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setDescription(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getCutoffDatetimeUtc();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setCutoffDatetimeUtc(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getDatetimeUtc();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setDatetimeUtc(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getEarliestPossibleDelivery();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setEarliestPossibleDelivery(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getLastPossibleDelivery();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setLastPossibleDelivery(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getDatetimeLocal();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setDatetimeLocal(string $string): InstahomeInterface;

    /**
     * @return string|null
     */
    public function getTextLocal();

    /**
     * @param string $string
     * @return InstahomeInterface
     */
    public function setTextLocal(string $string): InstahomeInterface;
}
