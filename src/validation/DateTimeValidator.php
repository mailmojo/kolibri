<?php
class DateTimeValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (is_array($this->model->$property)) {
			$var = $this->model->$property;
			$hour = isset($var['hours']) ? $var['hours'] : 0;
			$minute = isset($var['minutes']) ? $var['minutes'] : 0;
			$second = isset($var['seconds']) ? $var['seconds'] : 0;
			$day = isset($var['mday']) ? $var['mday'] : date('j');
			$month = isset($var['mon']) ? $var['mon'] : date('n');
			$year = isset($var['year']) ? $var['year'] : date('Y');

			$theDate = mktime((int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day, (int) $year);
			$date = new Date($theDate);
		}
		else {
			$date = DateFormat::parse($this->model->$property);
		}

		if ($date === null || !checkdate($date->getTimeField(Date::MONTH),
				$date->getTimeField(Date::DAY_OF_MONTH), $date->getTimeField(Date::YEAR))
				|| (min($date->getTimeField(Date::HOUR), $date->getTimeField(Date::MINUTE), 0) < 0)
				|| ($date->getTimeField(Date::HOUR) > 23) || ($date->getTimeField(Date::MINUTE) > 59)) {
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
