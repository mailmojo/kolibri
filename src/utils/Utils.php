<?php
/**
 * Simple static class for loading utility modules. To use any of the collections
 * of utility functions in the utils/ directory one should call Utils::import() with
 * the name of the module (file name without extension, i.e. 'files').
 */
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