<?php
class HexValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (ctype_xdigit($this->model->$property)) {
			return array('hex' => $rules['name']);
		}
		return true;
	}
}
?>
