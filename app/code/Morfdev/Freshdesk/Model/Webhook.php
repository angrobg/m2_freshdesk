<?php

namespace Morfdev\Freshdesk\Model;

use Psr\Log\LoggerInterface;
use Morfdev\Freshdesk\Model\Config as SystemConfig;

class Webhook
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
     * @param array $data
	 * @return void
     */
    public function sendData($data)
    {
		$destinationUrlList = $this->systemConfig->getDestinationUrlList();
		foreach ($destinationUrlList as $destinationUrl) {
			$ch = curl_init($destinationUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_exec($ch);
			curl_close($ch);
		}
    }
}
