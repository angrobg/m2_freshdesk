<?php
namespace Morfdev\Freshdesk\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressRenderer;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Morfdev\Freshdesk\Model\Webhook;
use Magento\Directory\Model\CountryFactory;

class CustomerCreatedObserver implements ObserverInterface
{
	/** @var Webhook  */
	protected $webhook;

	/** @var AddressRepositoryInterface  */
	protected $addressRepository;

	/** @var AddressRenderer */
	protected $addressRenderer;

	/** @var  AddressMapper */
	protected $addressMapper;

	/** @var CountryFactory  */
	protected $countryFactory;

	/**
	 * CustomerCreatedObserver constructor.
	 * @param AddressRepositoryInterface $addressRepository
	 * @param AddressRenderer $addressRenderer
	 * @param AddressMapper $mapper
	 * @param Webhook $webhook
	 * @param CountryFactory $countryFactory
	 */
	public function __construct(
		AddressRepositoryInterface $addressRepository,
		AddressRenderer $addressRenderer,
		AddressMapper $mapper,
		Webhook $webhook,
		CountryFactory $countryFactory
	) {
		$this->addressRepository = $addressRepository;
		$this->addressRenderer = $addressRenderer;
		$this->addressMapper = $mapper;
		$this->webhook = $webhook;
		$this->countryFactory = $countryFactory;
	}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$addressRenderer = $this->addressRenderer->getFormatByCode('html')->getRenderer();
		try {
			$billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
			$billingAddressFormatted = $addressRenderer->renderArray($this->addressMapper->toFlatArray($billingAddress));
			$address = [
				'address_1' => $billingAddress->getStreet()[0],
				'address_2' => $billingAddress->getStreet()[1],
				'city' => $billingAddress->getCity(),
				'state' => $billingAddress->getRegion()->getRegion(),
				'country' => $this->countryFactory->create()->loadByCode($billingAddress->getCountryId())->getName(),
				'postcode' => $billingAddress->getPostcode()

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
			'scope' => "customer.created",
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
