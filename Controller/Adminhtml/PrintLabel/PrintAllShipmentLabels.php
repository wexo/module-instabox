<?php

namespace Wexo\Instabox\Controller\Adminhtml\PrintLabel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Wexo\Instabox\Model\Api;

class PrintAllShipmentLabels extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    public function __construct(
        Context $context,
        Api $api,
        OrderRepositoryInterface $orderRepository,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->orderRepository = $orderRepository;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            $shipmentCollection = $order->getShipmentsCollection();
            foreach ($shipmentCollection as $shipment) {
                if ($pdf = $this->api->createShipmentLabel($shipment->getIncrementId())) {
                    $this->fileFactory->create(
                        $pdf['name'],
                        $pdf['content'],
                        DirectoryList::ROOT,
                        'application/pdf'
                    );
                }
            }
        }
        $this->_redirect($this->getUrl('sales/order/view/order_id/' . $orderId));
    }
}
