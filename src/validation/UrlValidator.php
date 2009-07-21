<?php
class UrlValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (!empty($this->model->$property)) {
			if (filter_var($this->model->$property, FILTER_VALIDATE_URL,
					array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED)) === false) {
				return array('url' => $rules['name']);
			}
		}
		return true;
	}
}
?>
