<?php
class ExistsValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (!isset($rules['condition'])) {
			if ($this->model->$property === null || $this->model->$property === ''
					|| $this->model->$property === array()) {
				return array('exists' => $rules['name']);
			}
			/*
			 * Checks if any of the values inside an array is empty.
			 * TODO: Though this does not work with a condition.
			 */
			if (is_array($this->model->$property) && !empty($this->model->$property)) {
				foreach ($this->model->$property as $value) {
					if (empty($value)) {
						return array('exists' => $rules['name']);
					}
				}
			}
		}
		else if (is_array($rules['condition'])) {
			foreach ($rules['condition'] as $qualifier => $qualifierValue) {
				if ($qualifier !== null) {
					if ((($qualifierValue !== null && $this->model->$qualifier == $qualifierValue)
								|| ($qualifierValue === null && !empty($this->model->$qualifier)))
							&& empty($this->model->$property)) {
						return array('exists' => $rules['name']);
					}
				}
			}
		}

		return true;
	}
}
?>
