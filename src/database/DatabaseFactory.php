<?php
final class DatabaseFactory {
	private static $connection;

	public static function getConnection () {
		if (!isset(self::$connection)) {
			$dbConf = Config::get('db');
			
			if (!empty($dbConf)) {		
				$type = $dbConf['type'] . 'Connection';
				$file = ROOT . "/database/$type.php";

				if (file_exists($file)) {
					require(ROOT . "/database/$type.php");
					self::$connection = new $type($dbConf);
				}
				else {
					throw new Exception("Database implementation $type not found");
				}
			}
			else {
				throw new Exception('No database configured');
			}
		}

		return self::$connection;
	}
}
?>
