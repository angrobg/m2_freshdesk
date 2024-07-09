<?php
namespace Morfdev\Freshdesk\Observer;

use Magento\Framework\Event\ObserverInterface;
use Morfdev\Freshdesk\Model\Webhook;

class OrderCreatedObserver implements ObserverInterface
{
	/** @var Webhook  */
	protected $webhook;

	/**
	 * OrderCreatedObserver constructor.
	 * @param Webhook $webhook
	 */
	public function __construct(
		Webhook $webhook
	) {
		$this->webhook = $webhook;
	}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		$data = [
			'scope' => "order.created",
			'email' => $order->getCustomerEmail(),
			'number' => $order->getIncrementId(),
			'amount' => $order->getBaseGrandTotal()
		];
		$this->webhook->sendData($data);
	}
}
