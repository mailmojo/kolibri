<?php
require(ROOT . '/database/DatabaseConnection.php');

/**
 * Factory class used to instantiate and return <code>DatabaseConnection</code> objects representing
 * a connection to a database. By using the <code>getConnection()</code> factory, the same object for
 * a given connection is returned over repeated invokations during the same PHP execution.
 */
final class DatabaseFactory {
	/**
	 * Keeps track of each connection object.
	 * @var array
	 */
	private static $connections;

	/**
	 * Restrict construction (this is a static factory class).
	 */
	private function __construct () {}

	/**
	 * Returns an implementation-specific <code>DatabaseConnection</code> for a connection to the
	 * database represented by the supplied configuration parameter. It defaults to the general 'db'
	 * configuration.
	 *
	 * @param array $confName Name of configuration parameter to return connection of.
	 * @throws Exception      If the implementation the configuration specifies is not found.
	 * @return DatabaseConnection
	 */
	public static function getConnection ($confName = null) {
		$confKey = ($confName === null ? 'database' : "database.{$confName}");
		if (!isset(self::$connections[$confKey])) {
			$dbConf = Config::get($confKey);

			if (!empty($dbConf)) {
				$dbConnClass = $dbConf['type'] . 'Connection';

				// If this implementation has not yet been required, do so here
				if (!class_exists($dbConnClass, false)) {
					$connectionFile = ROOT . "/database/$dbConnClass.php";

					if (file_exists($connectionFile)) {
						require($connectionFile);
					}
					else {
						throw new DatabaseException("Database implementation $dbConnClass not found");
					}
				}

				self::$connections[$confKey] = new $dbConnClass($dbConf);
			}
			else {
				throw new DatabaseException("No database configuration section named '$confKey'");
			}
		}

		return self::$connections[$confKey];
	}
}
?>
