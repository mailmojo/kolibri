<?php
class KolibriActionContext {
	public function get ($uri, array $get = null, array $post = null) {
		$rp = new RequestProcessor($get, $post);
		$response = $rp->process(false);
	}
}
?>
