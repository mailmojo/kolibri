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
	public static function getConnection ($confName = 'db') {
		if (!isset(self::$connections)) {
			$dbConf = Config::get($confName);

			if (!empty($dbConf)) {
				$connectionFile = ROOT . "/database/{$dbConf['type']}Connection.php";
				$resultSetFile = ROOT . "/database/{$dbConf['type']}ResultSet.php";

				// Require the implementation-specific files or throw error if not exists
				if (file_exists($connectionFile) && file_exists($resultSetFile)) {
					require($connectionFile);
					require($resultSetFile);
					$type = $dbConf['type'] . 'Connection';
					self::$connections[$confName] = new $type($dbConf);
				}
				else {
					throw new Exception("Database implementation for $type not found.");
				}
			}
			else {
				throw new Exception('No database configured.');
			}
		}

		return self::$connections[$confName];
	}
}
?>
