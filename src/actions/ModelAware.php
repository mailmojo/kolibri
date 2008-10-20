<?php
/**
 * This interface is used by actions that want a model auto-instantiated and populated with values from
 * request parameters. The action must expose a public <code>$model</code> property which will hold the
 * populated model. The <code>ModelInterceptor</code> must be configured for the action for this to have
 * any effect.
 *
 * @version		$Id: ModelAware.php 1523 2008-07-09 23:32:14Z anders $
 */
interface ModelAware {
	/**
	 * Returns the name of the model to instantiate and populate, or an array with the names and structure
	 * of models if the main model contains other models. If an array is to be returned, it must have a
	 * structure similar to the following example (it can be as deep as you want).
	 *
	 *     array('MainModelName', 'propertyInModel' => array('AnotherModelName'))
	 *
	 * @return mixed	Model class to instantiate.
	 */
	//public function getModelName ();
}
?>
