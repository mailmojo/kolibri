<?php
/**
 * This interface is used by actions that want a model validated prior to its execution, and any errors to
 * be displayed to the user. The interface does not define any methods, but the action should expose the
 * following properties:
 *
 * <ul>
 * <li><code>public $model</code> containing the model to validate.</li>
 * <li><code>public $errors</code> where any validation errors are stored.</li>
 * </ul>
 *
 * The <code>ValidatorInterceptor</code> must be configured for the action for this to have any effect.
 * Usually the <code>PrepareModelInterceptor</code> should be configured as well, to auto-populate the
 * <code>$model</code> property.
 *
 * @version		$Id: ValidationAware.php 1495 2008-05-16 18:00:13Z anders $
 */
interface ValidationAware {
}
?>
