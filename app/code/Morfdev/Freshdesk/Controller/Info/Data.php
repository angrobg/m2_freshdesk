<?php

namespace Morfdev\Freshdesk\Controller\Info;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Morfdev\Freshdesk\Api\CustomerManagementInterface;
use Morfdev\Freshdesk\Api\OrderRecentManagementInterface;
use Morfdev\Freshdesk\Model\Authorization;
use Psr\Log\LoggerInterface;
use Magento\Framework\Oauth\Helper\Request;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Morfdev\Freshdesk\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey;

class Data extends Action
{
    /** @var Authorization  */
    protected $authorization;

    /** @var CustomerManagementInterface  */
    protected $customerManagement;

    /** @var OrderRecentManagementInterface  */
    protected $orderRecentManagement;

    /** @var null  */
    private $postData = null;

    /** @var LoggerInterface  */
    protected $logger;

    /** @var Config  */
    protected $config;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param Authorization $authorization
     * @param CustomerManagementInterface $customerManagement
     * @param OrderRecentManagementInterface $orderRecentManagement
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param FormKey $formKey
     */
    public function __construct(
        Context $context,
        Authorization $authorization,
        CustomerManagementInterface $customerManagement,
        OrderRecentManagementInterface $orderRecentManagement,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Config $config,
        FormKey $formKey
    ) {
        parent::__construct($context);
        $this->_request->setParam('form_key', $formKey->getFormKey());
        $this->authorization = $authorization;
        $this->customerManagement = $customerManagement;
        $this->orderRecentManagement = $orderRecentManagement;
        $this->logger = $logger;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @return mixed|null
     */
    private function getPostData()
    {
        if (null !== $this->postData) {
            return $this->postData;
        }
        $this->postData = file_get_contents('php://input');
        if (false === $this->postData) {
            $this->logger->error(__('Invalid POST data'));
            return $this->postData = null;
        }
        $this->postData = json_decode($this->postData, true);
        if (null === $this->postData) {
            $this->logger->error(__('Invalid JSON'));
        }
        return $this->postData;
    }
    
    /**
     * Check authorization with Freshdesk account
     * @return bool
     */
    private function authorise()
    {
        return $this->authorization->isAuth($this->getPostData());
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            //CORS Preflight
            return $resultJson->setData([
                'order_list' => [],
                'customer_list' => []
            ]);
        }
        $scope = $this->authorise();
        if (null === $scope) {
            $resultJson->setHttpResponseCode(Request::HTTP_UNAUTHORIZED);
            return $resultJson->setData($scope);
        }
        try {
            $data = array_merge(
                $this->getCustomerInfo($scope), $this->getRecentOrderInfo($scope)
            );
        } catch (\Exception $e) {
            $resultJson->setHttpResponseCode(500);
            return $resultJson->setData([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        if (!$data) {
            return $resultJson->setData([
                'order_list' => [],
                'customer_list' => []
            ]);
        }
        return $resultJson->setData($data);
    }

    /**
     * @param integer|Website|Store $scope
     * @return array
     */
    private function getCustomerInfo($scope)
    {
        $result = [];
        $postData = $this->getPostData();
        if (null === $postData) {
            return $result;
        }
        $customerInfo = null;
        if (isset($postData['order_id'])) {
            $customerInfo = $this->customerManagement->getInfoFromOrder($postData['order_id'], $scope);
        }
        if (!$customerInfo && isset($postData['email'])) {
            $customerInfo = $this->customerManagement->getInfo($postData['email'], $scope);
        }
        if ($customerInfo) {
            $result = ['customer_list' => $customerInfo];
        }
        return $result;
    }

    /**
     * @param integer|Website|Store $scope
     * @return array
     */
    private function getRecentOrderInfo($scope)
    {
        $result = [];
        $postData = $this->getPostData();
        if (null === $postData) {
            return $result;
        }

        $orderItemInfo = null;
        if (isset($postData['order_id'])) {
            $orderItemInfo = $this->orderRecentManagement->getInfoFromOrder($postData['order_id'], $scope);
        }
        if (!$orderItemInfo && isset($postData['email'])) {
            $orderItemInfo = $this->orderRecentManagement->getInfo($postData['email'], $scope);
        }
        if ($orderItemInfo) {
            $result = ['order_list' => $orderItemInfo];
        }
        return $result;
    }
}