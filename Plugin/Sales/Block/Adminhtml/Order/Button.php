<?php

namespace Wexo\Instabox\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class Button
{
    public function beforeSetLayout(OrderView $subject)
    {
        $subject->addButton(
            'order_print_shipment_labels',
            [
                'label' => __('Print Shipment Labels'),
                'class' => __('print-shipment-labels'),
                'id' => 'order-view-print-shipment-labels',
                'onclick' => 'setLocation(\'' . $subject->getUrl('wexo_instabox/printLabel/printShipmentLabel') . '\')'
            ]
        );
    }
}
