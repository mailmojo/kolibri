<?php
class KolibriActionContext extends PHPSpec_Context {

	public function before () {
		/*if (ob_get_level() != 0) {
			ob_clean();
		}*/
		//ob_start();
	}

	public function after () {
		//ob_end_flush();
	}

	public function get ($uri, array $params = null, array $session = null) {
		$this->prepareEnvironment('GET', $uri, $session);
		$this->request = new Request($params !== null ? $params : array(), array());
		$this->fireRequest($this->request);
	}

	public function post ($uri, array $params = null, array $session = null) {
		$this->prepareEnvironment('POST', $uri, $session);
		$this->request = new Request(array(), $params !== null ? $params : array());
		$this->fireRequest($this->request);
	}

	private function fireRequest ($request) {
		$rp = new RequestProcessor($request);
		$this->response = $rp->process(false);
		$this->action = $rp->getDispatcher()->getAction();
	}

	private function prepareEnvironment ($method, $uri, $session) {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
		$_SESSION = $session !== null ? $session : array();
	}
}
?>
