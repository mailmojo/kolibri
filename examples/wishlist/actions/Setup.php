<?php
/**
 * Action for the setup page -- prepares database tables.
 */
class Setup extends ActionSupport {
	/**
	 * Displays the setup page if database is not already prepared.
	 */
	public function doGet () {
		$dbSetup = new DatabaseSetup();

		if ($dbSetup->isDone()) {
			$this->msg->setMessage('Database is already prepared.');
			return new RedirectResult($this, '/');
		}

		return new XsltResult($this, '/setup');
	}

	/**
	 * Sets up the database table.
	 */
	public function doPost () {
		$dbSetup = new DatabaseSetup();
		$dbSetup->setup();
		$this->msg->setMessage('Database prepared. All ready for wishes.');
		return new RedirectResult($this, '/');
	}
}
?>
