<?php

namespace Morfdev\Freshdesk\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Morfdev\Freshdesk\Model\Source\RedirectType;

class Redirect extends Action
{
    /** @var array  */
    protected $_publicActions = ['redirect'];
    
    /**
     * @return $this|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        if (null === $type || null === $id) {
            return $resultForward->forward('noroute');
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        switch ($type) {
            case RedirectType::CUSTOMER_TYPE:
                $resultRedirect->setPath('customer/index/edit', ['id' => $id]);
                break;
            case RedirectType::PRODUCT_TYPE:
                $resultRedirect->setPath('catalog/product/edit', ['id' => $id]);
                break;
            case RedirectType::ORDER_TYPE:
                $resultRedirect->setPath('sales/order/view', ['order_id' => $id]);
                break;
            default:
                return $resultForward->forward('noroute');
        }
        return $resultRedirect;
    }
}