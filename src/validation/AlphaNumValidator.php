<?php
/**
 * Validates a given string to only contain letters a-z (case insensitive), digits 0-9 or
 * underscore and normal hyphen characters.
 */
class AlphaNumValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (empty($this->model->$property)) {
			return true;
		}

		if (preg_match('/^[0-9a-zA-Z_-]*$/', $this->model->$property) != 1) {
			return array('alphanum' => $rules['name']);
		}

		return ValidationHelper::validateLength(mb_strlen($this->model->$property), $rules);
	}
}
?>
