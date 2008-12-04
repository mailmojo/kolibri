<?php
class InDbValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if ($this->model->$property !== null && $this->model->$property != '') {
			$dbRelation = (isset($rules['relation']) ? $rules['relation'] : false);

			if ($dbRelation) {
				$db = DatabaseFactory::getConnection();
				$dbCast = (isset($rules['type']) ? '::' . $rules['type'] : '');

				$query = "SELECT 1 FROM $dbRelation WHERE $property = ?{$dbCast}";
				if (!$db->getColumn($query, array($this->model->$property))) {
					return array('in_db' => array($rules['name'], $this->model->$property));
				}
			}
		}

		return true;
	}
}
?>
