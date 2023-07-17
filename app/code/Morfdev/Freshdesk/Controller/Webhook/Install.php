<?php

namespace Morfdev\Freshdesk\Controller\Webhook;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Morfdev\Freshdesk\Model\Authorization;
use Psr\Log\LoggerInterface;
use Magento\Framework\Oauth\Helper\Request;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

class Install extends Action
{
    /** @var Authorization  */
    protected $authorization;

	/** @var ConfigInterface  */
	private $resourceConfig;

    /** @var null  */
    private $postData = null;

    /** @var LoggerInterface  */
    protected $logger;

	/**
	 * Install constructor.
	 * @param Context $context
	 * @param Authorization $authorization
	 * @param LoggerInterface $logger
	 * @param ConfigInterface $resourceConfig
	 * @param FormKey $formKey
	 */
    public function __construct(
        Context $context,
        Authorization $authorization,
        LoggerInterface $logger,
		ConfigInterface $resourceConfig,
        FormKey $formKey
    ) {
        parent::__construct($context);
        $this->_request->setParam('form_key', $formKey->getFormKey());
        $this->authorization = $authorization;
        $this->logger = $logger;
        $this->resourceConfig = $resourceConfig;
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
        $scope = $this->authorise();
        if (null === $scope) {
            $resultJson->setHttpResponseCode(Request::HTTP_UNAUTHORIZED);
            return $resultJson->setData($scope);
        }
        try {
			$postData = $this->getPostData();
			if (null === $postData || !isset($postData['delivery_url']) || !isset($postData['type'])) {
				throw new \Exception("Error on install webhook");
			}
			$this->resourceConfig->saveConfig('morfdev_freshdesk/general/'. $postData['type'] . '_destination_url', $postData['delivery_url'], 'default', 0);
        } catch (\Exception $e) {
            $resultJson->setHttpResponseCode(500);
            return $resultJson->setData([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        return $resultJson->setData([]);
    }
}