<?php
/**
 * Validator for constraining a propertys value to equal to another propertys value.
 */
class EqualsValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		$equalsProperty = (isset($rules['equals']) ? $rules['equals'] : null);
		if ($equalsProperty !== null && $this->model->$property != $this->model->$equalsProperty) {
			$allRules = $this->model->rules();
			$equalsPropertyName = $allRules[$equalsProperty]['name'];
			return array('equals' => array($rules['name'], $equalsPropertyName));
		}

		return true;
	}
}
?>
