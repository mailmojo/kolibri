<?php
/**
 * This class is a validateable model proxy. This model proxy is used for <code>Validateable</code> models in order to
 * correctly expose their functionality.
 *
 * @version		$Id: ValidateableModelProxy.php 1542 2008-08-12 18:46:42Z anders $
 */
class ValidateableModelProxy extends ModelProxy implements Validateable {
	/**
	 * Creates a <code>ValidateableModelProxy</code> instance for the model supplied. It is assumed that
	 * the model has been verified <code>Validateable</code>.
	 *
	 * @param object $model		Model to proxy.
	 */
	public function __construct ($model/*, $dirty*/) {
		parent::__construct($model/*, $dirty*/);
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
