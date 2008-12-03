<?php
/**
 * Helper class to prepare the database for use and check if it's ready.
 */
class DatabaseSetup {
	/**
	 * Checks to see if our tables are ready to go.
	 */
	public function isDone () {
		$db = DatabaseFactory::getConnection();
		$dbCheck = <<<SQL
			SELECT 1 FROM sqlite_master 
				WHERE
					type = 'table'
					AND name = ?
SQL;
		return $db->getColumn($dbCheck, array('items'));
	}

	/**
	 * Sets up needed database tables.
	 */
	public function setup () {
		$db = DatabaseFactory::getConnection();
		$create = <<<SQL
			CREATE TABLE items (
				name TEXT PRIMARY KEY NOT NULL,
				description TEXT DEFAULT NULL,
				price NUMERIC DEFAULT NULL,
				added DATE NOT NULL,
				received DATE DEFAULT NULL
			)
SQL;
		$db->query($create);
	}
}
?>
