<?php
/**
 * Action for the display of the front page. Retrieves the wishlist items and returns the XSL page as the
 * result.
 */
class Index extends ActionSupport {
	public $items;

	/**
	 * As the name implies, doGet() is called for GET request. It must return an instance of a Result class, in 
	 * this case a XsltResult for a XSL transformation.
	 */
	public function doGet () {
		$dbSetup = new DatabaseSetup();
		if (!$dbSetup->isDone()) {
			// Database tables is not set up, so redirect to setup page
			return new RedirectResponse($this, '/setup');
		}

		$items = Models::init('Item');
		$this->items = $items->objects->findAll(); // Notice that this calls findAll() in the ItemDao class
		return new XsltResponse($this, '/index'); // Path relative to views directory, extension omitted
	}
}
?>
