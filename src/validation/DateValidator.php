<?php
class DateValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (is_array($this->model->$property)) {
			$var = $this->model->$property;
			$day = isset($var['mday']) ? (int)$var['mday'] : date('j');
			$month = isset($var['mon']) ? (int)$var['mon'] : date('n');
			$year = isset($var['year']) ? (int)$var['year'] : date('Y');

			if ($month > 0 && $day > 0 && $year > 0) {
				$theDate = mktime(0, 0, 0, $month, $day, $year);
				$date = new Date($theDate);
			}
			else {
				$date = null;
			}
		}
		else {
			$date = DateFormat::parse($this->model->$property);
		}

		if ($date === null || !checkdate($date->getTimeField(Date::MONTH),
				$date->getTimeField(Date::DAY_OF_MONTH), $date->getTimeField(Date::YEAR))) {
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
