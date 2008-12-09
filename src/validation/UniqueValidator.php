<?php
class UniqueValidator {
	private $model;

	public function __construct ($model) {
		if ($model instanceof ModelProxy) {
			$this->model = $model->extract();
		}
		else {
			$this->model = $model;
		}
	}

	public function validate ($property, $rules) {
		$reflection = new ReflectionObject($this->model);
		if (!$reflection->hasConstant('RELATION')) {
			throw new Exception('Model ' . get_class($this->model) . ' must define the RELATION constant
				for unique validation');
		}
		$relation = $reflection->getConstant('RELATION');
		$pk = $reflection->getConstant('PK');

		if (!empty($this->model->original)) {
			$originalPkVal = $this->model->original;
		}
		$pkVal = $this->model->$pk;

		if ($pk != $property || !isset($originalPkVal)
				|| (isset($originalPkVal) && $originalPkVal != $pkVal)) {
			$db = DatabaseFactory::getConnection();
			$whereString = '';
			$whereValues[] = $this->model->$property;

			// Include "not equal to PK"-check to allow editing of self
			if ($pk != $property && $pkVal !== null
					|| (isset($originalPkVal) && $originalPkVal != $pkVal)) {
				$whereString = "AND $pk <> ? ";
				$whereValues[] = (isset($originalPkVal) ? $originalPkVal : $pkVal);
			}

			// Should we be case-sensitive? Defaults to true.
			$sensitive = (isset($rules['sensitive']) ? $rules['sensitive'] : true);

			// If additional WHERE-requirements are defined, prepare them for query
			if (isset($rules['where']) && is_array($rules['where'])) {
				foreach ($rules['where'] as $column => $value) {
					$whereString .= ($sensitive ? "AND $column = ? " : "AND lower($column) = lower(?) ");
					$whereValues[] = $value;
				}
			}

			$query = "SELECT 1 FROM $relation WHERE "
				. ($sensitive ? "$property = ? " : "lower($property) = lower(?) ") . $whereString;
			if ($db->getColumn($query, $whereValues)) {
				// The query found a row -- it's not unique
				return array('unique' => array($name, $this->model->$property));
			}
		}

		return true;
	}
}
?>
