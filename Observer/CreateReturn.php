<?php

namespace Wexo\Instabox\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

class CreateReturn implements ObserverInterface
{
    /**
     * @var \Wexo\Instabox\Model\Api
     */
    protected $api;
    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Wexo\Instabox\Model\Api $api,
        LoggerInterface $logger,
        Json $json
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->json = $json;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditMemo = $observer->getEvent()->getCreditmemo();

            /** @var \Magento\Sales\Model\Order $order */
            $order = $creditMemo->getOrder();

            if (strpos($order->getShippingMethod(), 'instabox') !== false) {
                $response = $this->api->createReturn($order);
                $wexoShippingData = $order->getData('wexo_shipping_data') !== null
                    ? $order->getData('wexo_shipping_data')
                    : '{}';
                $shippingData = $this->json->unserialize($wexoShippingData);
                if (isset($shippingData['instabox'])) {
                    $shippingData['instabox']['return'] = $response;
                } else {
                    $shippingData['instabox'] = [
                        'return' => $response
                    ];
                }
                $order->setData('wexo_shipping_data', $this->json->serialize($shippingData));
            }
        } catch (\Exception $e) {
            $this->logger->error('Instabox Create Return', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
