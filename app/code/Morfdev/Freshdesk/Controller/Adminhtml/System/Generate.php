<?php

namespace Morfdev\Freshdesk\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Config\Model\Config\Factory as ConfigFactory;

class Generate extends Action
{
    /** @var ConfigFactory  */
    protected $configFactory;

    /**
     * @param Action\Context $context
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Action\Context $context,
        ConfigFactory $configFactory
    ) {
        parent::__construct($context);
        $this->configFactory = $configFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $section = $this->getRequest()->getParam('section');
            $website = $this->getRequest()->getParam('website');
            $store = $this->getRequest()->getParam('store');

            $configData = [
                'section' => $section,
                'website' => $website,
                'store' => $store,
                'groups' => ['general' => ['fields' => ['token' => ['value' => hash('sha256',time())]]]],
            ];
            /** @var \Magento\Config\Model\Config $configModel  */
            $configModel = $this->configFactory->create(['data' => $configData]);
            $configModel->save();
            $this->messageManager->addSuccessMessage(__('Token successfully created.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('adminhtml/system_config/edit',
            [
                '_current' => ['section', 'website', 'store'],
                '_nosid' => true
            ]
        );
    }
}