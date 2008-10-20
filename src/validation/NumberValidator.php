<?php
class NumberValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (empty($this->model->$property)) {
			return true;
		}

		// First we simply check for a number
		if (!is_numeric($this->model->$property)) {
			return array('num' => $rules['name']);
		}

		if (($result = ValidationHelper::validateSize($this->model->$property, $rules)) === true) {
			return ValidationHelper::validateLength(strlen($this->model->$property), $rules);
		}
		return $result;
	}
}
?>
