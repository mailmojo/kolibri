<?php
final class DatabaseFactory {
	private static $connections;

	public static function getConnection ($confName = 'db') {
		if (!isset(self::$connections)) {
			$dbConf = Config::get($confName);
			
			if (!empty($dbConf)) {		
				$connectionFile = ROOT . "/database/{$dbConf['type']}Connection.php";
				$resultSetFile = ROOT . "/database/{$dbConf['type']}ResultSet.php";

				if (file_exists($connectionFile) && file_exists($resultSetFile)) {
					require($connectionFile);
					require($resultSetFile);
					$type = $dbConf['type'] . 'Connection';
					self::$connections[$confName] = new $type($dbConf);
				}
				else {
					throw new Exception("Database implementation for $type not found");
				}
			}
			else {
				throw new Exception('No database configured');
			}
		}

		return self::$connections[$confName];
	}
}
?>
