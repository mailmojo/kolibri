<?php
class TextValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (strlen($this->model->$property) > 0) {
			// TODO: use strings::is_utf8()
			if (preg_match('/^.{1}/us', $this->model->$property) != 1) {
				return array('text' => $rules['name']);
			}
		}

		return ValidationHelper::validateLength(mb_strlen($this->model->$property), $rules);
	}
}
?>
