<?php

namespace Morfdev\Freshdesk\Model\Management;

use Morfdev\Freshdesk\Model\Config;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Morfdev\Freshdesk\Api\OrderRecentManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\UrlInterface;
use Morfdev\Freshdesk\Model\Source\RedirectType;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Morfdev\Freshdesk\Model\Source\RendererType;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Directory\Model\CountryFactory;


class OrderRecent implements OrderRecentManagementInterface
{
    /** @var OrderRepositoryInterface  */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface  */
    protected $orderItemRepository;

    /** @var OrderCollectionFactory  */
    protected $orderCollectionFactory;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var CurrencyInterface  */
    protected $currency;

    /** @var AddressRenderer  */
    protected $addressRenderer;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var UrlInterface  */
    protected $urlBuilder;

    /** @var CustomerRepositoryInterface  */
    protected $customerRepository;

    /** @var FilterBuilder  */
    protected $filterBuilder;

    /** @var TimezoneInterface  */
    protected $localeDate;

    /** @var SortOrderBuilder  */
    protected $sortOrderBuilder;

    /** @var RendererType  */
    protected $rendererType;

    /** @var ShipmentRepositoryInterface  */
    protected $shipmentRepository;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var OrderAddressRepositoryInterface  */
    protected $addressRepository;

    /** @var CustomerCollectionFactory  */
    protected $customerCollectionFactory;

	/** @var CountryFactory  */
	protected $countryFactory;

	/** @var Config  */
	protected $config;

	/**
	 * @param OrderRepositoryInterface $orderRepository
	 * @param OrderItemRepositoryInterface $orderItemRepository
	 * @param OrderCollectionFactory $orderCollectionFactory
	 * @param CurrencyInterface $currency
	 * @param AddressRenderer $addressRenderer
	 * @param StoreManagerInterface $storeManager
	 * @param SearchCriteriaBuilder $searchCriteriaBuilder
	 * @param UrlInterface $urlBuilder
	 * @param CustomerRepositoryInterface $customerRepository
	 * @param FilterBuilder $filterBuilder
	 * @param TimezoneInterface $localeDate
	 * @param SortOrderBuilder $sortOrderBuilder
	 * @param RendererType $rendererType
	 * @param ShipmentRepositoryInterface $shipmentRepository
	 * @param ScopeConfigInterface $scopeConfig
	 * @param OrderAddressRepositoryInterface $addressRepository
	 * @param CustomerCollectionFactory $customerCollectionFactory
	 * @param CountryFactory $countryFactory
	 * @param Config $config
	 */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderCollectionFactory $orderCollectionFactory,
        CurrencyInterface $currency,
        AddressRenderer $addressRenderer,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UrlInterface $urlBuilder,
        CustomerRepositoryInterface $customerRepository,
        FilterBuilder $filterBuilder,
        TimezoneInterface $localeDate,
        SortOrderBuilder $sortOrderBuilder,
        RendererType $rendererType,
        ShipmentRepositoryInterface $shipmentRepository,
        ScopeConfigInterface $scopeConfig,
        OrderAddressRepositoryInterface $addressRepository,
        CustomerCollectionFactory $customerCollectionFactory,
		CountryFactory $countryFactory,
		Config $config
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->currency = $currency;
        $this->storeManager = $storeManager;
        $this->addressRenderer = $addressRenderer;
        $this->urlBuilder = $urlBuilder;
        $this->customerRepository = $customerRepository;
        $this->filterBuilder = $filterBuilder;
        $this->localeDate = $localeDate;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->rendererType = $rendererType;
        $this->shipmentRepository = $shipmentRepository;
        $this->scopeConfig = $scopeConfig;
        $this->addressRepository = $addressRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
		$this->countryFactory = $countryFactory;
		$this->config = $config;
    }

    /**
     * @param string $incrementId
     * @param integer|Website|Store $scope
     * @return array
     */
    public function getInfoFromOrder($incrementId, $scope)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('increment_id', ['eq' => $incrementId]);

        if ($scope instanceof Website) {
            $orderCollection->addFieldToFilter('store_id', ['in' => $scope->getStoreIds()]);
        }
        if ($scope instanceof Store) {
            $orderCollection->addFieldToFilter('store_id', ['eq' => $scope->getId()]);
        }
        $orderList = $orderCollection->getItems();
        $orderInfo = [];
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        foreach ($orderList as $order) {
            $orderInfo = $this->getInfo($order->getCustomerEmail(), $scope);
            break;
        }
        return $orderInfo;
    }

    /**
     * @param string $email
     * @param int|Store|Website $scope
     * @return array
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Currency_Exception
     */
    public function getInfo($email, $scope)
    {
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection */
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addFieldToFilter('email', ['eq' => $email]);

        if ($scope instanceof Website) {
            $customerCollection->addFieldToFilter('website_id', ['eq' => $scope->getId()]);
        }
        if ($scope instanceof Store) {
            $customerCollection->addFieldToFilter('store_id', ['eq' => $scope->getId()]);
        }
        $customerList = $customerCollection->getItems();
        $customerIds = [];
        foreach ($customerList as $customer) {
            $customerIds[] = $customer->getId();
        }

        $filterList[] = $this->filterBuilder
            ->setField('customer_email')
            ->setConditionType('eq')
            ->setValue($email)
            ->create();

        $filterList[] = $this->filterBuilder
            ->setField('customer_id')
            ->setConditionType('in')
            ->setValue($customerIds)
            ->create();
        $storeFilter = [];
        if ($scope instanceof Website) {
            $storeFilter[] = $this->filterBuilder
                ->setField('store_id')
                ->setConditionType('in')
                ->setValue($scope->getStoreIds())
                ->create();
        }
        if ($scope instanceof Store) {
            $storeFilter[] = $this->filterBuilder
                ->setField('store_id')
                ->setConditionType('eq')
                ->setValue($scope->getId())
                ->create();
        }
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDescendingDirection()
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters($filterList)
            ->addFilters($storeFilter)
            ->addSortOrder($sortOrder)
            ->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
        $orderInfo = [];
        $isBaseCurrencyType = $this->config->getCurrencyType() !== 'store';
        /** @var OrderInterface $order */
        foreach ($orderList as $order) {
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                $shippingAddress = $billingAddress;
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $order->getEntityId(), 'eq')
                ->addFilter('parent_item_id', new \Zend_Db_Expr('null'), 'is')
                ->create();
            $orderItemsList = $this->orderItemRepository->getList($searchCriteria)->getItems();

            $currencyCode = $isBaseCurrencyType ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
            $currency = $this->currency->getCurrency($currencyCode);
            $orderItemInfo = [];
            /** @var OrderItemInterface $orderItem */
            foreach ($orderItemsList as $orderItem) {
                $redirectUrl = $this->urlBuilder->getUrl('md_freshdesk/index/redirect',
                    ['id' => $orderItem->getProductId(), 'type' => RedirectType::PRODUCT_TYPE]);

                $renderer = $this->rendererType->getProductRendererByType($orderItem->getProductType());
                $renderer->setItem($orderItem)->setArea('frontend');

                $orderItemInfo[] = [
                    'url' => $redirectUrl,
                    'product_id' => $orderItem->getProductId(),
                    'name' => $orderItem->getName(),
                    'product_html' => $renderer->toHtml(),
                    'sku' => $orderItem->getSku(),
                    'price' => $isBaseCurrencyType ? $currency->toCurrency($orderItem->getBasePrice()) : $currency->toCurrency($orderItem->getPrice()),
                    'ordered_qty' => (int)$orderItem->getQtyOrdered(),
                    'invoiced_qty' => (int)$orderItem->getQtyInvoiced(),
                    'shipped_qty' => (int)$orderItem->getQtyShipped(),
                    'refunded_qty' => (int)$orderItem->getQtyRefunded(),
                    'row_total' => $isBaseCurrencyType ? $currency->toCurrency($orderItem->getBaseRowTotal()) : $currency->toCurrency($orderItem->getRowTotal())
                ];
            }

			$billing = [
				'first_name' => '',
				'last_name' => '',
				'email' => '',
				'country' => '',
				'city' => '',
				'state' => '',
				'street' => '',
				'postcode' => '',
				'phone' => '',
			];
			$shipping = [
				'first_name' => '',
				'last_name' => '',
				'country' => '',
				'city' => '',
				'state' => '',
				'street' => '',
				'postcode' => '',
				'phone' => '',
			];
			if ($billingAddress) {
				$country = $this->countryFactory->create();
				$country->getResource()->load($country, $billingAddress->getCountryId());
				$countryName = $country->getName();

				$billing = [
					'first_name' => $billingAddress->getFirstname(),
					'last_name' => $billingAddress->getLastname(),
					'email' => $billingAddress->getEmail(),
					'country' => $countryName,
					'city' => $billingAddress->getCity(),
					'state' => $billingAddress->getRegion(),
					'street' => (is_array($billingAddress->getStreet()))? implode(', ', $billingAddress->getStreet()):'',
					'postcode' => $billingAddress->getPostcode(),
					'phone' => $billingAddress->getTelephone(),
				];
			}

			if ($shippingAddress) {
				$country = $this->countryFactory->create();
				$country->getResource()->load($country, $shippingAddress->getCountryId());
				$countryName = $country->getName();

				$shipping = [
					'first_name' => $shippingAddress->getFirstname(),
					'last_name' => $shippingAddress->getLastname(),
					'country' => $countryName,
					'city' => $shippingAddress->getCity(),
					'state' => $shippingAddress->getRegion(),
					'street' => (is_array($shippingAddress->getStreet()))? implode(', ', $shippingAddress->getStreet()):'',
					'postcode' => $shippingAddress->getPostcode(),
					'phone' => $shippingAddress->getTelephone(),
				];
			}
            $orderInfo[] = [
                'url' => $this->urlBuilder->getUrl('md_freshdesk/index/redirect',
                    ['id' => $order->getEntityId(), 'type' => RedirectType::ORDER_TYPE]),
                'order_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'store' => $this->storeManager->getStore($order->getStoreId())->getName(),
                'created_at' => $this->localeDate->formatDateTime($order->getCreatedAt(), \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::SHORT),
                'billing_address' => (null !== $billingAddress)?$this->addressRenderer->format($billingAddress, null):[],
                'shipping_address' => (null !== $shippingAddress)?$this->addressRenderer->format($shippingAddress, null):[],
				'billing' => $billing,
				'shipping' => $shipping,
                'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
                'shipping_method' => $order->getShippingDescription(),
                'shipping_tracking' => $this->prepareShippingTrackingForOrder($order),
                'status' => $order->getStatusLabel(),
                'state' => $order->getState(),
                'totals' => [
                    'subtotal' => $isBaseCurrencyType ? $currency->toCurrency($order->getBaseSubtotal()) : $currency->toCurrency($order->getSubtotal()),
                    'shipping' => $isBaseCurrencyType ? $currency->toCurrency($order->getBaseShippingAmount()) : $currency->toCurrency($order->getShippingAmount()),
                    'discount' => $isBaseCurrencyType ? $currency->toCurrency($order->getBaseDiscountAmount()) : $currency->toCurrency($order->getDiscountAmount()),
                    'tax' => $isBaseCurrencyType ? $currency->toCurrency($order->getBaseTaxAmount()) : $currency->toCurrency($order->getTaxAmount()),
                    'grand_total' => $isBaseCurrencyType ? $currency->toCurrency($order->getBaseGrandTotal()) : $currency->toCurrency($order->getGrandTotal())
                ],
                'items' => $orderItemInfo
            ];
        }
        return $orderInfo;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function prepareShippingTrackingForOrder(OrderInterface $order)
    {
        $shippingCollection = $order->getShipmentsCollection();
        $result = [];
        foreach ($shippingCollection as $shipmentItem) {
            try {
                $shipment = $this->shipmentRepository->get($shipmentItem->getId());
            } catch (NoSuchEntityException $e) {
                continue;
            }
            $trackList = $shipment->getAllTracks();
            foreach ($trackList as $track) {
                $carrier = $this->getCarrierName($track->getCarrierCode(), $order->getStoreId());
                $result[] = [
                    'carrier' => $carrier,
                    'number' => $track->getTrackNumber(),
                    'title' => $track->getTitle()
                ];
            }
        }
        return $result;
    }

    /**
     * @param string $carrierCode
     * @param null|integer|Store $store
     * @return mixed
     */
    private function getCarrierName($carrierCode, $store = null)
    {
        if ($name = $this->scopeConfig->getValue(
            'carriers/' . $carrierCode . '/title',
            ScopeInterface::SCOPE_STORE,
            $store
        )) {
            return $name;
        }
        return $carrierCode;
    }
}
