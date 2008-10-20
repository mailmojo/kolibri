<?php
class UniqueValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		$db = DatabaseFactory::getConnection();
		$relation = $this->model->relation();
		$pk = $this->model->pk();

		if (!empty($this->model->original)) {
			$originalPkVal = $this->model->original;
		}
		$pkVal = $this->model->$pk;

		if ($pk != $property || !isset($originalPkVal)
				|| (isset($originalPkVal) && $originalPkVal != $pkVal)) {
			$whereString = '';
			$whereValues[] = $this->model->$property;

			// Include "not equal to PK"-check to allow editing of self
			if ($pk != $property && $pkVal !== null
					|| (isset($originalPkVal) && $originalPkVal != $pkVal)) {
				$whereString = "AND $pk <> %s ";
				$whereValues[] = (isset($originalPkVal) ? $originalPkVal : $pkVal);
			}

			// Should we be case-sensitive? Defaults to true.
			$sensitive = (isset($rules['sensitive']) ? $rules['sensitive'] : true);

			// If additional WHERE-requirements are defined, prepare them for query
			if (isset($rules['where']) && is_array($rules['where'])) {
				foreach ($rules['where'] as $column => $value) {
					$whereString .= ($sensitive ? "AND $column = %s " : "AND lower($column) = lower(%s) ");
					$whereValues[] = $value;
				}
			}

			$query = "SELECT 1 FROM $relation WHERE "
				. ($sensitive ? "$property = %s " : "lower($property) = lower(%s) ") . $whereString;
			if ($db->getOne($query, $whereValues)) {
				// The query found a row -- it's not unique
				return array('unique' => array($name, $this->model->$property));
			}
		}

		return true;
	}
}
?>
