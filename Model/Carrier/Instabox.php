<?php

namespace Wexo\Instabox\Model\Carrier;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wexo\Instabox\Api\Carrier\InstaboxInterface;
use Wexo\Instabox\Api\Data\ParcelShopInterface;
use Wexo\Instabox\Model\Api;
use Wexo\Shipping\Api\Carrier\CarrierInterface;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Api\Data\RateInterface;
use Wexo\Shipping\Model\Carrier\AbstractCarrier;
use Wexo\Shipping\Model\RateManagement;

class Instabox extends AbstractCarrier implements InstaboxInterface
{
    public $_code = self::TYPE_NAME;

    /**
     * @var Api
     */
    private $instaboxApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Wexo\Instabox\Model\Config
     */
    private $config;
    /**
     * @var Json
     */
    private $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param RateManagement $rateManagement
     * @param MethodFactory $methodFactory
     * @param ResultFactory $resultFactory
     * @param Api $instaboxApi
     * @param Repository $assetRepository
     * @param StoreManagerInterface $storeManager
     * @param MethodTypeHandlerInterface|null $defaultMethodTypeHandler
     * @param array $methodTypeHandlers
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        RateManagement $rateManagement,
        MethodFactory $methodFactory,
        ResultFactory $resultFactory,
        Api $instaboxApi,
        \Wexo\Instabox\Model\Config $config,
        Repository $assetRepository,
        Json $json,
        StoreManagerInterface $storeManager,
        MethodTypeHandlerInterface $defaultMethodTypeHandler = null,
        array $methodTypeHandlers = [],
        array $data = []
    ) {
        $this->instaboxApi = $instaboxApi;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateManagement,
            $methodFactory,
            $resultFactory,
            $assetRepository,
            $storeManager,
            $defaultMethodTypeHandler,
            $methodTypeHandlers,
            $data
        );
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->json = $json;
    }

    /**
     * Type name that links to the Rate model
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return static::TYPE_NAME;
    }

    /**
     * @param RateRequest $request
     * @return bool|DataObject|Result|null
     * @throws NoSuchEntityException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->resultFactory->create();
        $rates = $this->rateManagement->getRates($this, true);
        $items = $request->getAllItems();
        if (empty($items)) {
            return $result;
        }

        /** @var Quote $quote */
        $quote = reset($items)->getQuote();

        /** @var RateInterface $rate */
        foreach ($rates as $rate) {
            if ($rate->getConditions() && !$rate->getConditions()->validate($quote->getShippingAddress())) {
                continue;
            }

            $storeId = $this->storeManager->getStore()->getId();
            if ($rate->getStoreId() && !in_array($storeId, explode(',', $rate->getStoreId()))) {
                continue;
            }

            if ($rate->getCustomerGroups()
                && !in_array($quote->getCustomerGroupId(), explode(',', $rate->getCustomerGroups()))) {
                continue;
            }

            if ($rate->getMethodType() === 'instahome') {
                $requestData = $request->getData();
                $instahomeDeliveries = $this->instaboxApi->getInstahome(
                    $quote->getCustomerEmail(),
                    $quote->getBillingAddress()->getTelephone(),
                    $requestData['dest_street'],
                    $requestData['dest_postcode'],
                    $requestData['dest_city'],
                    $requestData['dest_country_id'],
                    $requestData['base_currency']->getCurrencyCode(),
                    $requestData['all_items'],
                    $requestData['package_value_with_discount']
                );
                if ($this->instaboxApi->getShowInstahomeAsOption() &&
                    isset($instahomeDeliveries) &&
                    !empty($instahomeDeliveries)) {
                    $maxInstahomeDeliveries = $this->config->getMaxInstahomeDeliveries();
                    $x = 0;
                    foreach ($instahomeDeliveries as $delivery) {
                        if ($maxInstahomeDeliveries > 0 && $x >= $maxInstahomeDeliveries) {
                            break;
                        }
                        $method = $this->methodFactory->create();
                        $method->setData('carrier', $this->_code);
                        $method->setData('method', $this->makeMethodCode($rate) . $x);
                        $method->setData(
                            'method_title',
                            $this->config->getInstahomePrependTitle() . $delivery['description']
                        );

                        $result->append($method);
                        $x++;
                    }
                }
                continue;
            }

            try {
                $parcelShopTitle = $this->instaboxApi->getFirstParcelShopName();
                $showAsOption = $this->instaboxApi->getShowAsOption();
                if ($showAsOption) {
                    try {
                        $wexoShippingData = $this->json->unserialize($quote->getData('wexo_shipping_data'));
                        if (isset($wexoShippingData['parcelShop']) &&
                            isset($wexoShippingData['parcelShop']['company_name'])
                        ) {
                            $parcelShopTitle = $wexoShippingData['parcelShop']['company_name'];
                        }
                        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                    } catch (\InvalidArgumentException $exception) {
                        // wexo shipping data is empty, skip
                    }
                    /** @var Method $method */
                    $method = $this->methodFactory->create();
                    $method->setData('carrier', $this->_code);
                    $method->setData('carrier_title', $this->getTitle());
                    $method->setData('method', $this->makeMethodCode($rate));
                    if ($this->config->showParcelShopTitle()) {
                        if (empty($parcelShopTitle)) {
                            return $result;
                        }
                        $method->setData('method_title', $rate->getTitle() . ' ' . $parcelShopTitle);
                    } else {
                        $method->setData('method_title', $rate->getTitle());
                    }
                    $method->setPrice(
                        $request->getFreeShipping() && $rate->getAllowFree() ? 0 : $rate->getPrice()
                    );
                    $result->append($method);
                }
            } catch (\Exception $exception) {
                $this->_logger->error(
                    'Instabox CollectRates Exception Occured',
                    [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString()
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getParcelShops(
        $email,
        $phone,
        $street,
        $zip,
        $city,
        $country_code,
        $currency_code,
        $items,
        $grand_total
    ) {
        if (empty($zip)) {
            return [];
        }
        try {
            $parcelShops = $this->instaboxApi->getParcelShops(
                $email,
                $phone,
                $street,
                $zip,
                $city,
                $country_code,
                $currency_code,
                $items,
                $grand_total
            );
        } catch (Exception $e) {
            return [];
        }

        if (empty($parcelShops) || !$parcelShops) {
            return [];
        }

        return $parcelShops;
    }

    /**
     * @param ShippingMethodInterface $shippingMethod
     * @param Rate $rate
     * @param string|null $typeHandler
     * @return mixed
     */
    public function getImageUrl(ShippingMethodInterface $shippingMethod, Rate $rate, $typeHandler)
    {
        return $this->assetRepository->createAsset('Wexo_Instabox::images/instabox.svg', [
            'area' => Area::AREA_FRONTEND
        ])->getUrl();
    }
}
