<?php
/**
 * Action for deleting items. As the item to delete is specified by the last URI element (which
 * does not have a matching action file), it is implicitly put in the "id" request parameter.
 */
class Del implements MessageAware {
	/**
	 * TODO: We should really POST the form (and thus doPost()).
	 */
	public function doGet ($request) {
		// We could also do $request->get('id'), whatever you prefer
		$itemName = $request['id'];
		$item = Models::init('Item');

		// Tries to load the item (notice that this calls load() in the ItemDao class)
		if ($item->objects->load($itemName)) {
			// Item was successfully found, delete
			$item->delete();
			$this->msg->setMessage('Item successfully deleted.');
		}
		else {
			$this->msg->setMessage("Item with name $itemName not found.", false);
		}

		// Redirect back to front page. Messages in $this->msg will be retained.
		return new RedirectResponse('/');
	}
}
?>
