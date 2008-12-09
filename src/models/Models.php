<?php
/**
 * This is a helper class for instantiating models wrapped in a model proxy for added functionality.
 * 
 * @version		$Id: Models.php 1542 2008-08-12 18:46:42Z anders $
 */
abstract class Models {
	/**
	 * Initializes a model proxy of the specified model class.
	 *
	 * @param string $name Model class.
	 * @return ModelProxy  A proxy instance of the model.
	 */
	public static function init ($name) {
		return Models::getModel(new $name());
	}

	/**
	 * Wraps the supplied model instance or array of models in a model proxy and returns it.
	 *
	 * @param mixed $model Model instance or array of model instances.
	 * @return object      The model(s) proxied.
	 */
	public static function getModel ($model) {
		// If the model is already proxied, we can simply return it
		if ($model instanceof ModelProxy) {
			return $model;
		}

		// We only support proxying of arrays or models
		if ((!is_object($model) && !is_array($model)) || empty($model)) {
			return null;
		}

		// Extract an object to do the type check below
		$check = (is_array($model) ? current($model) : $model);

		if ($check instanceof Validateable) {
			return new ValidateableModelProxy($model);
		}
		else if ($check instanceof DataProvided) {
			return new ModelProxy($model);
		}

		// Unsupported object type, simply return it
		return $model;
	}
}
?>
