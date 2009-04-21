<?php
require_once(dirname(__FILE__) . '/../../SpecHelper.php');

class DescribeItemAddAction extends KolibriActionContext {
	public function itShouldSetModelInSessionWhenParamsAreInvalid () {
		$this->post('/items/add');
		$this->spec($this->request->session->get('model'))->should->beAnInstanceOf('ModelProxy');
	}

	public function itShouldHaveInvalidModelWhenParamsAreInvalid () {
		$this->post('/items/add');
		$this->spec($this->action->model)->shouldNot->beValid();
	}

	public function itShouldHaveValidModelWhenParamsAreValid () {
		$this->post('/items/add', array('name' => 'A thing'));
		$this->spec($this->action->model)->should->validate();
		$this->spec($this->action->msg->getMessage())->should->be('Item successfully added.');
	}
}
?>
