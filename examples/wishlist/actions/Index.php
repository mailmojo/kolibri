<?php
/**
 * Action for the display of the front page. Retrieves the wishlist items and returns a XSL
 * rendered page as the response. We also implement ModelAware so any validation errors when
 * adding items are displayed.
 */
class Index implements MessageAware, ModelAware {
	public $model;
	public $items;

	/**
	 * As the name implies, doGet() is called for GET request. It must return an instance of a
	 * Response class, in this case a XsltResponse for server-side XSL transformation.
	 */
	public function doGet () {
		$dbSetup = new DatabaseSetup();
		if (!$dbSetup->isDone()) {
			// Database tables is not set up, so redirect to setup page
			return new RedirectResponse('/setup');
		}

		// Notice that this calls findAll() in the ItemDao class
		$this->items = Models::init('Item')->objects->findAll();
		// Path is relative to views directory, extension omitted
		return new XsltResponse($this, '/index');
	}
}
?>
