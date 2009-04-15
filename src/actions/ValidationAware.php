<?php
/**
 * This interface is used by actions that want a model automatically validated prior to its
 * execution. The <code>ValidationInterceptor</code> must be configured for the action for this
 * to have any effect, as must <code>ModelInterceptor</code> for there to be any model to
 * validate.
 *
 * If validation fails the <code>validationFailed()</code> method on the action is called for
 * it to return the response it deems appropriate, which is usually a SeeOtherResponse back to
 * the form. If however validation succeeds, normal request processing proceeds.
 */
interface ValidationAware {
	/**
	 * Called when validation failed. A <code>Response</code> object must be returned, which
	 * will be rendered instead of continuing request processing.
	 */
	public function validationFailed ();
}
?>
