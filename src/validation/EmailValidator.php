<?php
class EmailValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (!empty($this->model->$property)) {
			if (preg_match('/^[\w\.-]+@([a-z0-9]+([\.\-]?[a-z\d]+)*\.)+[a-z]{2,7}$/ui',
					$this->model->$property) != 1) {
				return array('email' => $rules['name']);
			}
		}

		return ValidationHelper::validateLength(mb_strlen($this->model->$property), $rules);
	}
}
?>
