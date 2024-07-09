<?php

namespace Morfdev\Freshdesk\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CurrencyType implements OptionSourceInterface
{
	const BASE_CODE = 'base';
	const STORE_CODE = 'store';

	/**
	 * @return array
	 */
	public function getOptionArray()
	{
		return [
			self::BASE_CODE => __("Base Currency"),
			self::STORE_CODE => __("Store Currency")
		];
	}

	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = $this->getOptionArray();
		$result = [];
		foreach ($options as $value => $label) {
			$result[] = ['value' => $value, 'label' => $label];
		}
		return $result;
	}
}