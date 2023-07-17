<?php
namespace Morfdev\Freshdesk\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressRenderer;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Morfdev\Freshdesk\Model\Webhook;

class CustomerUpdatedObserver implements ObserverInterface
{
	/** @var Webhook  */
	protected $webhook;

	/** @var CustomerRepositoryInterface  */
	protected $customerRepository;

	/** @var AddressRepositoryInterface  */
	protected $addressRepository;

	/** @var AddressRenderer */
	protected $addressRenderer;

	/** @var  AddressMapper */
	protected $addressMapper;

	/**
	 * CustomerUpdatedObserver constructor.
	 * @param CustomerRepositoryInterface $customerRepository
	 * @param AddressRepositoryInterface $addressRepository
	 * @param AddressRenderer $addressRenderer
	 * @param AddressMapper $mapper
	 */
	public function __construct(
		CustomerRepositoryInterface $customerRepository,
		AddressRepositoryInterface $addressRepository,
		AddressRenderer $addressRenderer,
		AddressMapper $mapper,
		Webhook $webhook
	) {
		$this->customerRepository = $customerRepository;
		$this->addressRepository = $addressRepository;
		$this->addressRenderer = $addressRenderer;
		$this->addressMapper = $mapper;
		$this->webhook = $webhook;
	}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$customerAddress = $observer->getCustomerAddress();
		try {
			$customer = $this->customerRepository->getById($customerAddress->getCustomerId());
		} catch (\Exception $e) {
			$customer = null;
		}
		if (!$customer) {
			return;
		}

		$addressRenderer = $this->addressRenderer->getFormatByCode('html')->getRenderer();
		try {
			$billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
			$billingAddressFormatted = $addressRenderer->renderArray($this->addressMapper->toFlatArray($billingAddress));
			$address = [
				'address_1' => $customerAddress->getStreetLine(1),
				'address_2' => $customerAddress->getStreetLine(2),
				'city' => $customerAddress->getCity(),
				'state' => $customerAddress->getRegion(),
				'country' => $customerAddress->getCountryModel()->getName(),
				'postcode' => $customerAddress->getPostcode()

			];
			$phone = $billingAddress->getTelephone();
			$company = $billingAddress->getCompany();
		} catch (\Exception $e) {
			$billingAddressFormatted = null;
			$phone = null;
			$company = null;
			$address = [];
		}

		$data = [
			'scope' => "customer.updated",
			'email' => $customer->getEmail(),
			'first_name' => $customer->getFirstname(),
			'last_name' => $customer->getLastname(),
			'phone' => $phone,
			'addressFormatted' => $billingAddressFormatted,
			'address' => $address,
			'company' => $company
		];
		$this->webhook->sendData($data);
	}
}
