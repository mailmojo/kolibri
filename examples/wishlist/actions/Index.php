<?php
/**
 * Action for the display of the front page. Retrieves the wishlist items and returns the XSL
 * page as the result. We also implement ModelAware so any validation errors when adding items
 * are displayed.
 */
class Index extends ActionSupport implements ModelAware {
	public $model;
	public $items;

	/**
	 * As the name implies, doGet() is called for GET request. It must return an instance of a
	 * Result class, in this case a XsltResult for a XSL transformation.
	 */
	public function doGet () {
		$dbSetup = new DatabaseSetup();
		if (!$dbSetup->isDone()) {
			// Database tables is not set up, so redirect to setup page
			return new RedirectResult($this, '/setup');
		}

		$items = Models::init('Item');
		// Notice that this calls findAll() in the ItemDao class
		$this->items = $items->objects->findAll(); 
		// Path is relative to views directory, extension omitted
		return new XsltResult($this, '/index'); 
	}
}
?>
