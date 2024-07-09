<?php
namespace Morfdev\Freshdesk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
	const FRESHWORKS_CONFIG_API_TOKEN_PATH = 'morfdev_freshdesk/general/token';
	const FRESHWORKS_CONFIG_CURRENCY_TYPE_PATH = 'morfdev_freshdesk/general/currency_type';
	const FRESHWORKS_CONFIG_FRESHDESK_DESTINATION_URL_PATH = 'morfdev_freshdesk/general/freshdesk_destination_url';
	const FRESHWORKS_CONFIG_FRESHSALES_DESTINATION_URL_PATH = 'morfdev_freshdesk/general/freshsales_destination_url';

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
			self::FRESHWORKS_CONFIG_API_TOKEN_PATH
		);
	}

	/**
	 * @return string
	 */
	public function getCurrencyType()
	{
		return $this->scopeConfig->getValue(
			self::FRESHWORKS_CONFIG_CURRENCY_TYPE_PATH
		);
	}

	/**
	 * @return string|null
	 */
	public function getFreshdeskDestinationUrl()
	{
		return $this->scopeConfig->getValue(
			self::FRESHWORKS_CONFIG_FRESHDESK_DESTINATION_URL_PATH
		);
	}

	/**
	 * @return string|null
	 */
	public function getFreshsalesDestinationUrl()
	{
		return $this->scopeConfig->getValue(
			self::FRESHWORKS_CONFIG_FRESHSALES_DESTINATION_URL_PATH
		);
	}

	/**
	 * @return array
	 */
	public function getDestinationUrlList()
	{
		$list = [];
		$freshdeskUrl = $this->getFreshdeskDestinationUrl();
		if ($freshdeskUrl) {
			$list[] = $freshdeskUrl;
		}
		$freshsalesUrl = $this->getFreshsalesDestinationUrl();
		if ($freshsalesUrl) {
			$list[] = $freshsalesUrl;
		}
		return $list;
	}
}
