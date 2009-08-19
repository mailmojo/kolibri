<?php
/**
 * Validator for constraining a propertys value to match a certain regular expression.
 */
class MatchesValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		$matchPattern = (isset($rules['matches']) ? $rules['matches'] : null);
		if ($matchPattern !== null && preg_match($matchPattern, $this->model->$property) == 0) {
			return array('matches' => array($rules['name']));
		}

		return true;
	}
}
?>