<?php

namespace Wexo\Instabox\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class Button
{
    public function beforeSetLayout(OrderView $subject)
    {
        $subject->addButton(
            'order_print_shipment_label',
            [
                'label' => __('Print Shipment Label'),
                'class' => __('print-shipment-label'),
                'id' => 'order-view-print-shipment-label',
                'onclick' => 'setLocation(\'' . $subject->getUrl('wexo_instabox/printLabel/printShipmentLabel') . '\')'
            ]
        );
    }
}
