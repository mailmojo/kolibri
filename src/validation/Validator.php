<?php
/**
 * This class handles validation of models, by examining the defined rules of a model and and passing the
 * actual validation along to concret validator classes. Using this class should rarely be done manually,
 * but rather through the <code>ValidatorInterceptor</code>.
 * 
 * @see			conf/validation.php
 * @version		$Id: Validator.php 1504 2008-06-12 22:22:32Z anders $
 */
class Validator {
	/**
	 * Holds validation rule constants mapped to names of validator classes.
	 * @var array
	 * @see conf/validation.php
	 */
	private $classes;

	/**
	 * Holds readable validation messages.
	 * @var array
	 * @see conf/validation.php
	 */
	private $messages;

	/**
	 * Initializes this validator with the supplied validator classes and validation messages supported.
	 *
	 * @param array $classes	Validator classes supported.
	 * @param array $messages	Validation messages supported.
	 */
	public function __construct ($classes, $messages) {
		$this->classes	= $classes;
		$this->messages	= $messages;
	}

	/**
	 * Validates a model and returns <code>true</code> if the model validates, <code>false</code>
	 * if not.
	 * 
	 * Any validation errors are put into a two-dimensional array with the specifics and set in
	 * $errors on the model. The array keys refer to the property of the model whose validation
	 * failed, while the value is an array containing human-readable messages of the specifics.
	 *
	 * @param object $model		The model to validate.
	 * @return bool				Indicating success or failure.
	 */
	public function validate ($model) {
		$ruleSet	= $model->rules();
		$instances	= array();
		$errors		= array();

		foreach ($ruleSet as $property => $rules) {
			$flags = $rules[0];

			foreach ($this->classes as $constant => $validatorClass) {
				// Checks if the current property has a rule defined for this validator
				if ($flags & $constant) {

					if (!isset($instances[$validatorClass])) {
						/*
						 * Require and cache validator class as we can reuse the same instance for validating
						 * other properties on the same model.
						 */
						require(ROOT . "/validation/$validatorClass.php");
						$instances[$validatorClass] = new $validatorClass($model);
					}

					$results = $instances[$validatorClass]->validate($property, $rules);
					if ($results !== true) {
						/*
						 * Validation errors occured. Set readable error message and abort
						 * further validation of the current property.
						 */
						$errors[$property] = $this->setValidationMessages($results);
						break;
					}
				}
			}
		}
		
		if (!empty($errors)) {
			$model->errors = $errors;
			$model->isValid = false;
		}
		else $model->isValid = true;
		
		return $model->isValid;
	}

	/**
	 * Sets validation messages for a property validation failure, according to the strings
	 * specified in the configuration variable <code>$validationMessages</code>.
	 *
	 * @param array $errors		Array of validation failures as returned from a validator class.
	 * @return array			Array of validation failures as human-readable strings.
	 */ 
	private function setValidationMessages ($errors) {
		foreach ($errors as $key => $value) {
			if (!is_array($value)) {
				$errors[$key] = sprintf($this->messages[$key], $value);
			}
			else {
				$errors[$key] = vsprintf($this->messages[$key], $value);
			}
		}

		return $errors;
	}
}
?>
