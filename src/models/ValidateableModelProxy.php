<?php
/**
 * This class is a validateable model proxy. This model proxy is used for <code>Validateable</code>
 * models in order to correctly expose their functionality.
 */
class ValidateableModelProxy extends ModelProxy implements Validateable {
	
	/**
	 * @var Validator
	 */
	private $validator;
	
	private $isValid;
	
	/**
	 * Creates a <code>ValidateableModelProxy</code> instance for the model supplied. It is assumed
	 * that the model has been verified <code>Validateable</code>.
	 *
	 * @param object $model		Model to proxy.
	 */
	public function __construct ($model) {
		parent::__construct($model);
	}
	
	private function init () {
		if (!isset($this->validator)) {
			$conf = Config::getValidationConfig();
			$this->validator = new Validator($conf['classes'], $conf['messages']);
		}
	}
	
	public function save () {
		if (!$this->validate()) {
			return false;
		}
		
		return parent::save();
	}
	
	public function validate () {
		$this->proxifyInnerModels();
		$this->init();
		$isValid = true;
		
		foreach ($this->models as $model) {
			$isValid = ($this->validateModel($model) ? $isValid : false);
			foreach ($model as $property) {
				if ($property instanceof ValidateableModelProxy) {
					$property->validate();
				}
			}
		}
		
		return $isValid;
	}
	
	protected function validateModel ($model) {
		if (property_exists($model, 'isValid')) {
			return $model->isValid;
		}
		
		return $this->validator->validate($model);
	}
	
	public function isValid () {
		return $this->validate();
	}
	
	protected function modelChanged ($model) {
		parent::modelChanged($model);
		unset($model->isValid);
	}

	/**
	 * Calls <code>rules()</code> on the current model and returns its result.
	 *
	 * @return array	Validation rules for the current model.
	 */
	public function rules () {
		return $this->current->rules();
	}
}
?>
