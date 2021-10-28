<?php

namespace Wexo\Instabox\Model\MethodType;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Throwable;
use Wexo\Instabox\Model\Api;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Model\MethodType\AbstractParcelShop;

class Instahome extends AbstractParcelShop implements MethodTypeHandlerInterface
{
    /**
     * @var Json
     */
    protected $jsonSerializer;
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var ObjectFactory
     */
    protected $objectFactory;
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var null
     */
    protected $parcelShopClass;

    public function __construct(
        Json $jsonSerializer,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        Api $api,
        $parcelShopClass = null
    ) {
        parent::__construct(
            $jsonSerializer,
            $dataObjectHelper,
            $objectFactory,
            $parcelShopClass
        );
        $this->jsonSerializer = $jsonSerializer;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->objectFactory = $objectFactory;
        $this->parcelShopClass = $parcelShopClass;
        $this->api = $api;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return __('Instahome');
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'instahome';
    }

    /**
     * @param CartInterface $quote
     * @param OrderInterface $order
     * @throws LocalizedException|Throwable
     */
    public function saveOrderInformation(CartInterface $quote, OrderInterface $order)
    {
        $shippingMethod = $order->getShippingMethod();
        $shippingMethod = explode('_', $shippingMethod);
        $sortCode = count($shippingMethod) === 3 ? array_pop($shippingMethod) : '0';

        if ($sortCode) {
            $prebooking = $this->api->createPreBooking($sortCode, $quote, $order);
            $shippingData['instabox'] = [
                'prebooking' => $prebooking
            ];
            $order->setData('wexo_shipping_data', $this->jsonSerializer->serialize($shippingData));
        } else {
            throw new LocalizedException(__('Sort code was not found'));
        }
    }
}
