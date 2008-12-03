<?php
/**
 * Action for deleting items. As the item to delete is specified by the last URI element (which does not have 
 * a matching action file), it is implicitly put in the "id" request parameter.
 */
class Del extends ActionSupport {
	/**
	 * TODO: Should really change to POST via form (and thus doPost()).
	 */
	public function doGet () {
		$itemName = $this->request['id']; // We could also do $this->request->get('id'), whatever you prefer
		$item = Models::init('Item');

		// Tries to load the item (notice that this calls load() in the ItemDao class)
		if ($item->objects->load($itemName)) {
			// Item was successfully found, delete
			if ($item->delete()) {
				$this->msg->setMessage('Item successfully deleted.');
			}
		}
		else {
			$this->msg->setMessage("Item with name $itemName not found.", false);
		}

		return new RedirectResult($this, '/'); // Redirect back to front page, notice messages are retained
	}
}
?>
