<?php
class Utils {
	public static function import ($module) {
		$path = ROOT . "/utils/{$module}.php";
		
		if (file_exists($path)) {
			require_once($path);
		}
		else {
			throw new Exception("Utility module '$module' does not exist.");
		}
	}
}
?>