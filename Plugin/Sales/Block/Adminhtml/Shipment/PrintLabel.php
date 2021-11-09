<?php

namespace Wexo\Instabox\Plugin\Sales\Block\Adminhtml\Shipment;

use Magento\Shipping\Block\Adminhtml\View as ShippingView;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class PrintLabel
{
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    public function __construct(ShipmentRepositoryInterface $shipmentRepository)
    {
        $this->shipmentRepository = $shipmentRepository;
    }

    public function beforeSetLayout(ShippingView $subject)
    {
        $subject->addButton(
            'print_shipment_label',
            [
                'label' => __('Print Shipment Label'),
                'class' => __('print-shipment-label'),
                'id' => 'shipment-view-print-label',
                'onclick' => 'setLocation(\'' .
                    $subject->getUrl('wexo_instabox/printLabel/printShipmentLabel', [
                        'shipment_id' => $subject->getShipment()->getId(),
                        'come_from' => $subject->getRequest()->getParam('come_from')
                    ]) .
                    '\')'
            ]
        );
    }
}
