<?php
class MatchesValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		$matchProperty = (isset($rules['match']) ? $rules['match'] : null);
		if ($matchProperty !== null && $this->model->$property != $this->model->$matchProperty) {
			$allRules = $this->model->rules();
			$matchPropertyName = $allRules[$matchProperty]['name'];
			return array('match' => array($rules['name'], $matchPropertyName));
		}

		return true;
	}
}
?>
