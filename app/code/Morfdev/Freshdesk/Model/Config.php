<?php
namespace Morfdev\Freshdesk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const FRESHDESK_CONFIG_API_TOKEN_PATH = 'morfdev_freshdesk/general/token';

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getApiTokenForDefault()
    {
        return $this->scopeConfig->getValue(
            self::FRESHDESK_CONFIG_API_TOKEN_PATH
        );
    }
}
