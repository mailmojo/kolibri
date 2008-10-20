<?php
class DateValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		$date = DateFormat::parse($this->model->$property);
		if (!checkdate($date->getTimeField(Date::MONTH), $date->getTimeField(Date::DAY_OF_MONTH),
				$date->getTimeField(Date::YEAR))) {
			return array('date' => $rules['name']);
		}

		if (isset($rules['minsize'])) {
			$epoch['minsize'] = DateFormat::parse($rules['minsize'])->getTime();
		}
		if (isset($rules['maxsize'])) {
			$epoch['maxsize'] = DateFormat::parse($rules['maxsize'])->getTime();
		}

		if (isset($epoch)) {
			return ValidationHelper::validateSize($date->getTime(), $rules, $epoch);
		}

		return true;
	}
}
?>
