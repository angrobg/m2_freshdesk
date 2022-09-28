<?php

namespace Morfdev\Freshdesk\Model;

use Psr\Log\LoggerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Morfdev\Freshdesk\Model\Config as SystemConfig;

class Authorization
{

    /** @var LoggerInterface  */
    protected $logger;

    /** @var SystemConfig */
    protected $systemConfig;

    /**
     * Authorization constructor.
     * @param LoggerInterface $logger
     * @param \Morfdev\Freshdesk\Model\Config $systemConfig
     */
    public function __construct(
        LoggerInterface $logger,
        SystemConfig $systemConfig
    ) {
        $this->logger = $logger;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param null|array $postData
     * @return null|integer|Store|Website
     */
    public function isAuth($postData)
    {
        $result = null;
        if(null === $postData || !isset($postData['token'])) {
            $this->logger->error('No authorisation token provided.');
            return $result;
        }
        //check is default token
        $storeToken =  $this->systemConfig->getApiTokenForDefault();
        if($postData['token'] == $storeToken) {
            $result = Store::DEFAULT_STORE_ID;
            return $result;
        }
        $this->logger->error('Authorisation failed.');
        return $result;
    }
}
