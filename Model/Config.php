<?php

namespace Wexo\Instabox\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public $configurationToken = null;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getIsEnabled()
    {
        return $this->scopeConfig->getValue(
            'carriers/instabox/active',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    public function getDisableBooking()
    {
        return $this->scopeConfig->getValue(
            'carriers/instabox/disable_booking',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    public function getClientId()
    {
        return $this->scopeConfig->getValue(
            'carriers/instabox/client_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getClientSecret()
    {
        return $this->scopeConfig->getValue(
            'carriers/instabox/client_secret',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCustomerNumber()
    {
        return $this->scopeConfig->getValue(
            'carriers/instabox/customer_number',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function showParcelShopTitle()
    {
        return $this->scopeConfig->getValue(
                'carriers/instabox/parcel_shop_title',
                ScopeInterface::SCOPE_STORE
            ) ?? false;
    }

    public function getInstahomePrependTitle(): string
    {
        return trim(
                $this->scopeConfig->getValue(
                    'carriers/instabox/instahome_prepend_title',
                    ScopeInterface::SCOPE_STORE
                )
            ) . ' ' ?? '';
    }

    public function getMaxInstahomeDeliveries(): string
    {
        return $this->scopeConfig->getValue(
                'carriers/instabox/max_instahome_deliveries',
                ScopeInterface::SCOPE_STORE
            ) ?? '0';
    }

    public function getCountryByWebsite(): string
    {
        return $this->scopeConfig->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreName(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/name',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStorePhone(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/phone',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStoreZip(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/zip',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStoreCity(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/city',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStoreStreet1(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/street_line1',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStoreStreet2(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/street_line1',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    public function getStoreCountry(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/country_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }
}
