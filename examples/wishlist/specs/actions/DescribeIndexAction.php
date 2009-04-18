<?php
class DescribeIndexAction extends KolibriActionContext {
	public function itShouldReturnXsltResponse () {
		$response = $this->get('/');
		$this->spec($response)->should->beAnInstanceOf('XsltResult');
	}
}
?>
