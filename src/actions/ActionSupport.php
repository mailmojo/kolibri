<?php
require(ROOT . '/actions/SessionAware.php');
require(ROOT . '/actions/MessageAware.php');

/**
 * This class provides support-functionality needed by most actions. It is entirely optional for
 * actions to extend this class, but most will.
 *
 * @version		$Id: ActionSupport.php 1518 2008-06-30 23:43:38Z anders $
 */
class ActionSupport implements SessionAware, MessageAware, Exposable {
	/**
	 * The processing request.
	 * @var Request
	 */
	protected $request;

	/**
	 * The HTTP session, if a <code>SessionInterceptor</code> is in use.
	 * @var Session
	 */
	public $session;
	
	/**
	 * Message facility which may be used to return a message to the user. This is only set if the
	 * <code>MessageInterceptor</code> is invoked, else it is empty.
	 * @var Message
	 */
	public $msg;
	
	/**
	 * Constructor.
	 *
	 * @param Request $request	The request object representing the current HTTP request.
	 */
	public function __construct ($request) {
		$this->request = $request;
	}
}
?>
