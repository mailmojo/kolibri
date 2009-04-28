<?php
require_once(dirname(__FILE__) . '/../SpecHelper.php');

class DescribeIndexAction extends KolibriContext {
	public function itShouldReturnXsltResponse () {
		$this->get('/');
		$this->spec($this->response)->should->beAnInstanceOf('XsltResponse');
	}

	public function itShouldHaveModelSetWhenInSession () {
		$item = Models::getModel(new Item());
		$this->get('/', array(), array('model' => $item));
		$this->spec($this->action->model)->should->beAnInstanceOf('ModelProxy');
	}
}
?>