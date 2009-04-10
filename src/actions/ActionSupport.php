<?php
require(ROOT . '/actions/MessageAware.php');

/**
 * This class provides support-functionality needed by most actions. It is entirely optional for
 * actions to extend this class, but most will.
 */
class ActionSupport implements MessageAware {
	/**
	 * Message facility which may be used to return a message to the user. This is only set if the
	 * <code>MessageInterceptor</code> is invoked, else it is empty.
	 * @var Message
	 */
	public $msg;
}
?>
