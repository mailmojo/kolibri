<?php
/**
 * Action for marking items as received.
 */
class Have implements MessageAware {
	/**
	 * TODO: We should really POST the form (and thus doPost()).
	 */
	public function doGet ($request) {
		$item = Models::init('Item');
		if ($item->objects->load($request['id'])) {
			// Set received date (could be done in SQL, but just to show date library in use)
			$df = DateFormat::getInstance(DateFormat::ISO_8601_DATE);
			$item->received = $df->format(new Date());

			$item->save();
			$this->msg->setMessage('Item successfully marked as received.');
		}
		else {
			$this->msg->setMessage("Item with name {$request['id']} not found.", false);
		}

		return new RedirectResponse('/');
	}
}
?>
