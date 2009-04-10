<?php
/**
 * This interface defines the contract of an interceptor.
 */
interface Interceptor {
	/**
	 * Initializes any resources required by the interceptor before use. This is called for all
	 * active interceptors before the first one is invoked.
	 */
	public function init ();

	/**
	 * Cleans up any resources the interceptor acquired in <code>init()</code>.
	 */
	public function destroy ();

	/**
	 * Executes this interceptor, by doing some processing before and/or after the rest of the
	 * request processing. Interceptors can short-circuit the processing by returning a
	 * <code>Result</code> itself, or delegate further processing of the request through
	 * <code>$dispatcher->invoke()</code>.
	 *
	 * @param Dispatcher $dispatcher Dispatcher handling the request processing flow.
	 * @return Result                The result of the request.
	 */
	public function intercept ($dispatcher);
}
?>
