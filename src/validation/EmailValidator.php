<?php
class EmailValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (!empty($this->model->$property)) {
			if (preg_match('/^\A[a-z0-9_+\-]+(\.[a-z0-9_+\-]+)*@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/ui',
					$this->model->$property) != 1) {
				return array('email' => $rules['name']);
			}
		}

		return ValidationHelper::validateLength(mb_strlen($this->model->$property), $rules);
	}
}
?>
