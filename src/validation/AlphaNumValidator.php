<?php
class AlphaNumValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (empty($this->model->$property)) {
			return true;
		}

		if (preg_match('/^[\x30-\x39\x41-\x5A\x61-\x7A]*$/', $this->model->$property) != 1) {
			return array('alphanum' => $rules['name']);
		}

		return ValidationHelper::validateLength(mb_strlen($this->model->$property), $rules);
	}
}
?>
