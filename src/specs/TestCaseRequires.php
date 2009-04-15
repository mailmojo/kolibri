<?php
define(ROOT, '/home/stian/projects/kolibri-test/src');

require(ROOT . '/core/Config.php');

// Actions
require_once(ROOT . '/actions/MessageAware.php');
require_once(ROOT . '/actions/ModelAware.php');
require_once(ROOT . '/actions/ValidationAware.php');

// Database
require_once(ROOT . '/database/ResultSet.php');
require_once(ROOT . '/database/ResultSetArray.php');
require_once(ROOT . '/database/DatabaseFactory.php');
require_once(ROOT . '/database/PostgreSqlConnection.php');
require_once(ROOT . '/database/SqlException.php');
require_once(ROOT . '/database/SqliteConnection.php');

// Models 
require_once(ROOT . '/models/DataProvided.php');
require_once(ROOT . '/models/ModelProxy.php');
require_once(ROOT . '/models/Models.php');
require_once(ROOT . '/models/Validateable.php');
require_once(ROOT . '/models/ValidateableModelProxy.php');

// Validation
require_once(ROOT . '/validation/ValidationHelper.php');
require_once(ROOT . '/validation/Validator.php');

// Fixtures
require_once(ROOT . '/specs/fixtures/Fixtures.php');


/**
 * Loads model, DAO or util files. Used internally by __autoload(), or by the user for importing utils like 
 * this:
 *
 *   import('urls', 'util');
 */
function import ($file, $type = 'model') {
	static $files;

	switch ($type) {
		case 'model': $path = MODELS_PATH; break;
		case 'dao': $path = MODELS_PATH . '/dao'; break;
		case 'util': $path = ROOT . '/utils'; break;
		default: throw new Exception("Could not import $file. Type $type is unknown.");
	}

	$filePath = $path . "/$file.php";
	if (!isset($files[$filePath])) {
		if (!file_exists($filePath)) {
			return false;
		}

		$files[$filePath] = true;
		require($filePath);
	}

	return true;
}

?>