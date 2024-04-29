<?php

namespace Morfdev\Freshdesk\Model\Management;

use Morfdev\Freshdesk\Api\CustomerManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Backend\Model\UrlInterface;
use Morfdev\Freshdesk\Model\Source\RedirectType;
use Magento\Customer\Model\Address\Config as AddressRenderer;
use Magento\Customer\Model\Address\Mapper as AddressMapper;

class Customer implements CustomerManagementInterface
{
    const GUEST_GROUP_LABEL = 'Guest';
    
    /** @var CustomerRepositoryInterface  */
    protected $customerRepository;

    /** @var CustomerCollectionFactory  */
    protected $customerCollectionFactory;

    /** @var OrderCollectionFactory  */
    protected $orderCollectionFactory;

    /** @var CurrencyInterface  */
    protected $currency;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var GroupRepositoryInterface  */
    protected $groupRepository;

    /** @var AddressRepositoryInterface  */
    protected $addressRepository;

    /** @var CountryFactory  */
    protected $countryFactory;

    /** @var TimezoneInterface  */
    protected $localeDate;

    /** @var UrlInterface  */
    protected $urlBuilder;

    /** @var AddressRenderer */
    protected $addressRenderer;

    /** @var  AddressMapper */
    protected $addressMapper;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CurrencyInterface $currency
     * @param StoreManagerInterface $storeManager
     * @param GroupRepositoryInterface $groupRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param CountryFactory $countryFactory
     * @param TimezoneInterface $localeDate
     * @param UrlInterface $urlBuilder
     * @param AddressRenderer $addressRenderer
     * @param AddressMapper $mapper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        OrderCollectionFactory $orderCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        CurrencyInterface $currency,
        StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        AddressRepositoryInterface $addressRepository,
        CountryFactory $countryFactory,
        TimezoneInterface $localeDate,
        UrlInterface $urlBuilder,
        AddressRenderer $addressRenderer,
        AddressMapper $mapper
    ) {
        $this->customerRepository = $customerRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->currency = $currency;
        $this->addressRepository = $addressRepository;
        $this->countryFactory = $countryFactory;
        $this->localeDate = $localeDate;
        $this->urlBuilder = $urlBuilder;
        $this->addressRenderer = $addressRenderer;
        $this->addressMapper = $mapper;
    }

    /**
     * @param string $email
     * @param int|\Magento\Store\Model\Store|\Magento\Store\Model\Website $scope
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Currency_Exception
     */
    public function getInfo($email, $scope)
    {
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addFieldToFilter('email', ['eq' => $email]);
        if ($scope instanceof \Magento\Store\Model\Website) {
            $customerCollection->addFieldToFilter('website_id', ['eq' => $scope->getId()]);
        }
        if ($scope instanceof \Magento\Store\Model\Store) {
            $customerCollection->addFieldToFilter('website_id', ['eq' => $scope->getWebsiteId()]);
        }
        $customerList = $customerCollection->getItems();
        $customerInfo = [];
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        foreach ($customerList as $customer) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
            $orderCollection = $this->orderCollectionFactory->create();
            $emailCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_email',
                ['eq' => $customer->getEmail()]);
            $idCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_id',
                ['eq' => $customer->getId()]);
            $orderCollection->getSelect()->where("({$emailCondition} OR {$idCondition})");

            $website = $this->storeManager->getWebsite($customer->getWebsiteId());
            if ($scope instanceof \Magento\Store\Model\Store) {
                $orderCollection->addFilter('store_id', ['eq' => $scope->getId()], 'public');
            } else {
                $orderCollection->addFilter('store_id', ['in' => $website->getStoreIds()], 'public');
            }

            $orderCollection->getSelect()->reset(Select::COLUMNS);
            $orderCollection->getSelect()->columns(
                [
                    'total_sales' => 'SUM(main_table.base_grand_total)'
                ]
            );
            
            $totalSales = $orderCollection->getFirstItem()->getTotalSales();
            $currency = $this->currency->getCurrency($website->getBaseCurrencyCode());

            //get country name
            try {
                $address = $this->addressRepository->getById($customer->getDefaultBilling());
                $country = $this->countryFactory->create();
                $country->getResource()->load($country, $address->getCountryId());
                $countryName = $country->getName();
            } catch (\Exception $e) {
                $countryName = '';
				$address = null;
            }
            
            //get customer group code
            try {
                $groupCode = $this->groupRepository->getById($customer->getGroupId())->getCode();
            } catch (\Exception $e) {
                $groupCode = '';
            }

            $addressRenderer = $this->addressRenderer->getFormatByCode('html')->getRenderer();
            //get default billing address
            try {
                $billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
                $billingAddressFormatted = $addressRenderer->renderArray($this->addressMapper->toFlatArray($billingAddress));
            } catch (\Exception $e) {
                $billingAddressFormatted = null;
            }

            //get shipping billing address
            try {
                $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                $shippingAddressFormatted = $addressRenderer->renderArray($this->addressMapper->toFlatArray($shippingAddress));
            } catch (\Exception $e) {
                $shippingAddressFormatted = null;
            }

            $meta = [
				'firstname' => $customer->getFirstname(),
				'lastname' => $customer->getLastname(),
				'country' => $countryName,
				'work_number' => '',
				'address' => '',
				'city' => '',
				'state' => '',
				'zipcode' => '',
				'job_title' => ''
			];

            if ($address) {
				$meta['work_number'] = $address->getTelephone() ? $address->getTelephone(): '';
				$meta['address'] = $address->getStreet()[0] ? $address->getStreet()[0]: '';
				if (isset($address->getStreet()[1])) {
					$meta['address'] .= ' ' . $address->getStreet()[1];
				}
				$meta['city'] = $address->getCity() ? $address->getCity(): '';
				$meta['state'] = $address->getRegion() ? $address->getRegion()->getRegion(): '';
				$meta['zipcode'] = $address->getPostcode() ? $address->getPostcode(): '';
				$meta['job_title'] = $address->getCompany() ? $address->getCompany(): '';
			}

            $customerInfo[] = [
                'url' => $this->urlBuilder->getUrl('md_freshdesk/index/redirect',
                    ['id' => $customer->getId(), 'type' => RedirectType::CUSTOMER_TYPE]),
                'customer_id' => $customer->getId(),
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'email' => $customer->getEmail(),
                'website_id' => $customer->getWebsiteId(),
                'website_name' => $website->getName(),
                'group' => $groupCode,
                'country' => $countryName,
                'total_sales' => $currency->toCurrency($totalSales),
                'created_at' => $this->localeDate->formatDateTime($customer->getCreatedAt(), \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::NONE),
                'billing_address' => $billingAddressFormatted,
                'shipping_address' => $shippingAddressFormatted,
				'meta' => $meta
            ];
        }
        return $customerInfo;
    }

    /**
     * @param string $incrementId
     * @param int|\Magento\Store\Model\Store|\Magento\Store\Model\Website $scope
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Currency_Exception
     */
    public function getInfoFromOrder($incrementId, $scope)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('increment_id', ['eq' => $incrementId]);

        if ($scope instanceof \Magento\Store\Model\Website) {
            $orderCollection->addFieldToFilter('store_id', ['in' => $scope->getStoreIds()]);
        }
        if ($scope instanceof \Magento\Store\Model\Store) {
            $orderCollection->addFieldToFilter('store_id', ['eq' => $scope->getId()]);
        }
        $orderList = $orderCollection->getItems();
        $customerInfo = [];
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        foreach ($orderList as $order) {
            if ($customerId = $order->getCustomerId()) {
                $customer = $this->customerRepository->getById($customerId);
                return $this->getInfo($customer->getEmail(), $scope);
            }
            $billingAddress = $order->getBillingAddress();
            if (null === $billingAddress) {
                return $this->getInfo($order->getCustomerEmail(), $scope);
            }
            try {
                $country = $this->countryFactory->create();
                $country->getResource()->load($country, $billingAddress->getCountryId());
                $countryName = $country->getName();
            } catch (\Exception $e) {
                $countryName = '';
            }
            $customerInfo[] = [
                'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'email' => $billingAddress->getEmail(),
                'group' => self::GUEST_GROUP_LABEL,
                'country' => $countryName
            ];
        }
        return $customerInfo;
    }
}
