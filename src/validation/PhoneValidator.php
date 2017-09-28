<?php
class PhoneValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (empty($this->model->$property)) {
			return true;
		}

		try {
			$phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$region = !empty($rules['region']) ? $rules['region'] : null;
			$phoneNumber = $phoneNumberUtil->parse($this->model->$property, $region);

			if (!empty($rules['type'])) {
				$type = $this->mapType($rules['type']);
				$result = $phoneNumberUtil->isPossibleNumberForTypeWithReason(
					$phoneNumber, $type
				) === \libphonenumber\ValidationResult::IS_POSSIBLE;
			}
			else {
				$result = true;
			}
		}
		catch (\libphonenumber\NumberParseException $e) {
			$result = false;
		}

		return $result === true ? true : array('phone' => $rules['name']);
	}

	private function mapType ($type) {
		switch ($type) {
			case 'mobile':
				return \libphonenumber\PhoneNumberType::MOBILE;
			case 'landline':
				return \libphonenumber\PhoneNumberType::FIXED_LINE;
			default:
				return \libphonenumber\PhoneNumberType::UNKNOWN;
		}
	}
}
