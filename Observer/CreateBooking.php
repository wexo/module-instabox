<?php

namespace Wexo\Instabox\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Shipment;

class CreateBooking implements ObserverInterface
{
    /**
     * @var \Wexo\Instabox\Model\Api
     */
    protected $api;
    /**
     * @var Json
     */
    private $json;

    public function __construct(
        \Wexo\Instabox\Model\Api $api,
        Json $json
    ) {

        $this->api = $api;
        $this->json = $json;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        if ($order->getShippingMethod()) {
            $response = $this->api->createBooking($order, $shipment);
            $shippingData = $this->json->unserialize($order->getData('wexo_shipping_data'));
            if (isset($shippingData['instabox'])) {
                $shippingData['instabox']['order'] = $response;
            } else {
                $shippingData['instabox'] = [
                    'order' => $response
                ];
            }
            $order->setData('wexo_shipping_data', $this->json->serialize($shippingData));
        }
    }
}
