<?php
/**
 * This interface is used by actions that want a model auto-instantiated and populated with
 * values from request parameters (or after a redirect from validation, the session). The
 * action must expose a public <code>$model</code> property which will hold the populated model.
 * 
 * For a model to be instantiated and populated, the <code>ModelInterceptor</code> must be
 * configured for the action, and the action must provide the name (or object) of the model
 * to use. This can be done either by setting a default value on the $model property like so:
 *
 *     public $model = 'ModelName';
 *
 * Or, if a model with inner models should be created:
 *
 *     public $model = array('MainModelName', 'propertyInModel' => array('AnotherModelName'));
 *
 * Alternatively, you can return an already instantiated model by implementing a
 * <code>getModel()</code> method. If present, this takes precedence over names specified in
 * $model.
 */
interface ModelAware {}
?>
