<?php
/**
 * This interface is used by actions that want to return messages to the user. The
 * <code>MessageInterceptor</code> must be configured for the action in order to prepare the
 * message facility for use, which will be set in an implicit <code>$msg</code> property on
 * the action.
 */
interface MessageAware {}
?>
