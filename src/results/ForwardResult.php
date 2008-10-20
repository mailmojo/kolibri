<?php
/**
 * Provides a result which forwards the request to another action. After the action has been
 * invoked, a final result is rendered. What final result to render can be specified when
 * instantiating this class. If no result was specified in the instantiation of this class, the
 * result of the action invoked will be rendered.
 * 
 * @version		$Id: ForwardResult.php 334 2006-05-21 20:42:54Z anders $
 */
class ForwardResult extends BaseResult {
	/** Result to render after action invocation, or NULL if the action result is to be rendered. */
	var $end_result;
	
	/**
	 * Constructor.
	 *
	 * @param Request &$request		The request object representing the current HTTP request.
	 * @param string $uri			URI to forward the request to. An action mapper must be able
	 * 								to map this URI to an action, or else nothing is rendered.
	 * @param object $end_result	Optional result to render after the forward action is complete.
	 * 								If not supplied, the result of the action is rendered.
	 * @return BaseResult
	 */
	function ForwardResult (&$request, $uri, $end_result = null) {
		parent::BaseResult($request);
		
		// Change the URI of the request
		$this->request->uri = $uri;
		
		if (!empty($end_result)) {
			$this->end_result = $end_result;
		}
	}
	
	/**
	 * Maps the target URI specified in this result to an action and dispatches the request. When
	 * the action returns we render our end result if provided, or else the result of the action.
	 */
	function render() {
		// Map the action we want to forward to
		$mapper = new ActionMapper();
		$mapping = $mapper->map($this->request);
		
		if (!empty($mapping)) {
			$dispatcher = new Dispatcher($this->request, $mapping);
			$result = $dispatcher->invoke();
			
			// Render the end result if we have one, else the result of the invoked action
			if (!empty($this->end_result)) {
				$this->end_result->render();
			}
			else {
				$result->render();
			}
		}
	}
}
?>