<?php
/**
 * Action for marking items as received.
 */
class Have extends ActionSupport {
	// TODO: We should really POST instead...
	public function doGet ($request) {
		$item = Models::init('Item');
		if ($item->objects->load($request['id'])) {
			// Set received date (could of course be done in SQL, but just to show date library in use)
			$df = DateFormat::getInstance(DateFormat::ISO_8601_DATE);
			$item->received = $df->format(new Date());

			if ($item->save()) {
				$this->msg->setMessage('Item successfully marked as received.');
			}
		}
		else {
			$this->msg->setMessage("Item with name {$this->request['id']} not found.", false);
		}

		return new RedirectResponse('/');
	}
}
?>
