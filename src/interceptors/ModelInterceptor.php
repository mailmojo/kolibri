<?php
/**
 * Interceptor which prepares a model with data from the request parameters, or extracts a model
 * which was temporarily stored in the session. The target action must be
 * <code>ModelAware</code> to subscribe to any of this interceptor's functionality.
 *
 * This interceptor works in two "modes": It either extracts a model already present in the
 * session if the request is a GET request, or it instantiates and populates a model with data
 * from the request parameters. For the latter to work, the target action must provide the name
 * of the model (along with any inner models) in a public <code>$model</code> property, or
 * return a pre-instantiated model from a <code>getModel()</code> method. We then loop through
 * request parameters and populate the model.
 *
 * Regardless of the "mode", the model found/prepared is put into the <code>$model</code>
 * property of the action.
 */
class ModelInterceptor extends AbstractInterceptor {
	private $modelNames = array();
	
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

		if ($action instanceof ModelAware) {
			// We depend on a $model-property in ModelAware actions
			if (!property_exists($action, 'model')) {
				$class = get_class($action);
				throw new Exception('Action ' . $class
						. ' is ModelAware and must define a public $model property.');
			}

			/*
			 * If model is availible from session we use that, but only if this is a GET
			 * request. The reason for this is that any new POST-submit of a form should
			 * take precedence, to stop any invalid model in the session to override the newly
			 * POSTed.
			 */
			if ($dispatcher->getRequest()->getMethod() == 'GET'
					&& $action instanceof SessionAware && isset($action->session['model'])) {
				$action->model = $action->session['model'];
				// Model has been extracted, remove it from session
				$action->session->remove('model');
			}
			// Otherwise prepare a model from request parameters
			else {
				if (method_exists($action, 'getModel')) {
					// The action supplies an already instantiated model
					$model = $action->getModel();
				}
				else {
					// The action supplies model class name(s), so we must instantiate
					$model = $this->instantiateModel($action->model);
				}
				
				if ($model !== null) {
					foreach ($dispatcher->getRequest()->params as $param => $value) {
						if (strpos($param, '::') !== false) {
							/*
							 * Parameter is a property path to inner models. Explode the path
							 * and populate.
							 */
							$exploded = explode('::', $param);
							$this->populate($model, $exploded, $value);
						}
						else {
							if (property_exists($model, $param) || $param == 'original') {
								$model->$param = $this->convertType($value);
							}
						}
					}

					// Prepare a ModelProxy for the model
					$action->model = Models::getModel($model);
				}
			}
		}

		return $dispatcher->invoke();
	}

	/**
	 * Instantiates the model as specified by the action and passed to this method. The model name specified
	 * may either be a single string with the model name, or an array structure where the main model
	 * contains other models.
	 *
	 * @param array $structure	Model name (along with any inner model structure) to instantiate.
	 * @return object
	 */
	private function instantiateModel ($structure) {
		if (is_string($structure)) {
			$model = new $structure();
		}
		else if (is_array($structure)) {		
			// With an array structure for models, the first array element must be the main model class
			$mainModel = array_shift($structure);
			$model = new $mainModel();

			foreach ($structure as $prop => $class) {
				if (is_numeric($prop) || !property_exists($model, $prop)) continue;

				if (is_array($model->$prop)) {
					// Await creating a model instance until populating the array property
					$this->modelNames[$prop] = $class;
				}
				else {
					if (is_string($class)) {
						$inner = new $class();
					}
					else {
						// $class is an array, recurse to handle inner classes
						$inner = $this->instantiateModel($class);
					}

					$model->$prop = $inner;
				}
			}
		}
		else return null;
		
		$model->isDirty = true;
		return $model;
	}

	/**
	 * Populates a specific property of a model with a value. The property is a <em>property path</em>
	 * of the form <code>outerProperty::innerProperty</code> in which case <code>outerProperty</code> in
	 * the model must be another model with the an <code>innerProperty</code> property to be populated
	 * with the value.
	 * 
	 * TODO: This must be better documented and possibly add property_exists()-checks
	 *
	 * @param object $model		Model object to populate.
	 * @param string $property	Property to populate.
	 * @param mixed $value		Property value.
	 */
	private function populate ($model, $property, $value) {
		for ($i = 0; $i < count($property); $i++) {
			$currentProp = $property[$i];

			if (property_exists($model, $currentProp)) {
				if (is_object($model->$currentProp)) {
					$this->populate($model->$currentProp, array_slice($property, $i + 1), $value);
					break;
				}
				else if (is_array($model->$currentProp) && is_array($value)) {
					if (!isset($this->modelNames[$currentProp])) break;

					$modelClass = $this->modelNames[$currentProp];
					$nextProperty = array_slice($property, $i + 1);

					foreach ($value as $index => $currentValue) {
						if ($currentValue == '') continue;

						if (!isset($model->{$currentProp}[$index])) {
							$model->{$currentProp}[$index] = $this->instantiateModel($modelClass);
						}

						$this->populate($model->{$currentProp}[$index], $nextProperty, $currentValue);
					}
					break;
				}
			}

			$model->$currentProp = $this->convertType($value);
		}
	}

	/**
	 * Converts textual values from the input to actual PHP types. Currently booleans and empty strings to
	 * nulls are implemented.
	 * 
	 * @param string $value		Value from input.
	 * @return mixed			Converted value.
	 */
	private function convertType ($value) {
		if ($value == 'true' || $value == 'false') {
			return ($value == 'true' ? true : false);
		}
		if ($value == '') {
			return null;
		}

		return $value;
	}
}
?>
