<?php
/**
 * This class is the Kolibri Test framework.
 *
 * It serves as a class for model, action and integration testing. To use this test framework,
 * simply extend KolibriContext and include SpecHelper. This class reflects the same methods
 * as PHPSPec_Context, but they are named differently. The corresponding method names are
 * setUp(), preSpec(), postSpec() and tearDown().
 */
class KolibriContext extends PHPSpec_Context {
	/**
	 * Holds the fixtures loaded, only populated during model testing.
	 * @var Fixtures
	 */
    protected $fixtures;

	/**
	 * Name of the model we are testing, if we are model testing.
	 * @var string
	 */
    protected $modelName;

	/**
	 * The browser through Selenium during integration testing.
	 * @var Testing_Selenium
	 */
	protected $browser;

	/**
	 * A reference to the database connection.
	 * @var DatabaseConnection
	 */
    protected $db;

	/**
	 * The current test type, corresponding to one of the class constants.
	 * @var string
	 */
	protected $testType;

	// Constants used to identify type of testing
	const ACTION_TEST      = 'action';
	const INTEGRATION_TEST = 'integration';
	const MODEL_TEST       = 'model';

	/**
	 * Internal initialization method which figures out what type of testing is currently being
	 * done; either model, action or integration testing. It also establishes a database
	 * connection which is used to begin/rollback any changes between each spec.
	 */
	protected function init () {
		if (Config::getMode() != Config::TEST) {
			throw new Exception('KolibriTestCase requires that the current KOLIBRI_MODE is set
					to TEST.');
		}

		// Figure out the filename of the current class
        $className = get_class($this);
		$reflection = new ReflectionClass($className);
		$classFilename = $reflection->getFileName();
		
		// Figure out which "testing type directory" we are within
		do {
			$currentPath = (isset($currentPath) ? $currentPath . '/..' : $classFilename);
			$mainDir = basename(dirname(realpath($currentPath)));
			$parentDir = basename(dirname(realpath($currentPath . '/..')));
		} while (
				$parentDir !== 'specs' &&
				$parentDir !== 'actions' &&
				$parentDir !== 'models' &&
				$parentDir !== 'integration'
			);

		// If class name ends with 'model' or we are within the models dir; model testing
        if (($inName = (substr(strtolower($className), -5) == self::MODEL_TEST))
				|| $parentDir == 'models') {
			$this->testType = self::MODEL_TEST;
			if ($inName) {
	            $this->modelName = substr($className, 8, -5);
			}
			else $this->modelName = ucfirst($mainDir);
        }
		// Else if class name ends with 'action' or we are within actions dir; action testing
        else if ($mainDir == 'actions'
				|| $parentDir == 'actions'
				|| substr(strtolower($className), -6) == self::ACTION_TEST) {
            $this->testType = self::ACTION_TEST;
        }
		// Else if we are within the integration dir; integration testing
        else if ($mainDir == 'integration' || $parentDir == 'integration') {
			$this->testType = self::INTEGRATION_TEST;
			$this->browser = self::getBrowserInstance();

			/*
			 * We register a shutdown function to stop the browser after the very last
			 * test -- we don't want to start/stop the browser all the time.
			 */
			register_shutdown_function(array('KolibriContext', 'stopBrowserInstance'));
        }
        else {
            throw new Exception('KolibriContext classes must be within one of the directories
					specs/actions, specs/integration or specs/models.');
        }

		$this->db = DatabaseFactory::getConnection();
	}

    /**
     * Executes before all spec methods are invoked, and triggers the setUp() method if
	 * present.
     */
    public function beforeAll () {
		$this->init();

		/*
		 * For integration tests, we re-prepare the database for each test class, as we
		 * can't roll back to reset state (given that integration tests are run through an
		 * external browser).
		 * XXX: We /might/ want to put this in before(), but it's heavy, so let's ponder...
		 */
		if ($this->testType == self::INTEGRATION_TEST) {
			prepare_database();
		}
		if (method_exists($this, 'setUp')) {
			$this->setUp();
		}
    }

	/**
	 * Starts a new database transaction before each spec and refreshes any fixtures for
	 * models specs. Also triggers a preSpec() method, if defined, for doing something
	 * _before_ a spec has been invoked.
	 */
    public function before () {
		if ($this->testType == self::MODEL_TEST) {
			$this->fixtures = new Fixtures($this->modelName);
		}
		
		// No use wrapping examples in a transaction for integration tests
		if ($this->testType != self::INTEGRATION_TEST) {
			$this->db->begin();
		}

		if (method_exists($this, 'preSpec')) {
			$this->preSpec();
		}
    }

	/**
	 * Triggers the postSpec() method for doing something _after_ a spec. It also rolls back
	 * the current changes in the database.
	 */
    public function after () {
		if (method_exists($this, 'postSpec')) {
			$this->postSpec();
		}

		// No use wrapping examples in a transaction for integration tests
		if ($this->testType != self::INTEGRATION_TEST) {
			$this->db->rollback();
		}
    }

	/**
	 * Triggers the tearDown() method for doing something _after all_ specs has runned.
	 */
    public function afterAll () {
		if (method_exists($this, 'tearDown')) {
			$this->tearDown();
		}

        unset($this->fixtures);
        unset($this->modelName);
		unset($this->db);

		/*
		 * Flush the output buffer only if buffering is on. In practice this is only when
		 * testing through the web browser (and test.php).
		 */
		if (ob_get_level() > 0) {
			ob_flush();
		}
    }

	/**
	 * Fires a GET request for testing an action. Any supplied parameteres and session
	 * data are passed along to the request. This provides access to the <code>$request</code>,
	 * <code>$response</code> and <code>$action</code> for the specs.
	 */
	public function get ($uri, array $params = null, array $session = null) {
		if ($this->validActionClass('get')) {
			$this->prepareEnvironment('GET', $uri, $session);
			$this->request = new Request($params !== null ? $params : array(), array());
			$this->fireRequest($this->request);
		}
	}

	/**
	 * Fires a POST request for testing an action. Any supplied parameteres and session
	 * data are passed along to the request. This provides access to the <code>$request</code>,
	 * <code>$response</code> and <code>$action</code> for the specs.
	 */
	public function post ($uri, array $params = null, array $session = null) {
		if ($this->validActionClass('post')) {
			$this->prepareEnvironment('POST', $uri, $session);
			$this->request = new Request(array(), $params !== null ? $params : array());
			$this->fireRequest($this->request);
		}
	}

	/**
	 * Helper method to actually fire the supplied request.
	 */
	private function fireRequest ($request) {
		$rp = new RequestProcessor($request);
		$this->response = $rp->process(false);
		$this->action = $rp->getDispatcher()->getAction();
	}

	/**
	 * Prepares the environment for a new request, by setting the request method, URI and
	 * session data.
	 *
	 * @param string $method The request method to set.
	 * @param string $uri    The request URI to set.
	 * @param array $session Data to set in the session.
	 */
	private function prepareEnvironment ($method, $uri, $session) {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
		$_SESSION = ($session !== null ? $session : array());
	}

	/**
	 * Does not allow you to use post and/or get in any other testing classes than action.
	 *
	 * @param string $method Method that is tested for.
	 * @return bool          Returns true if its allowed to be used.
	 */
	private function validActionClass ($method) {
		if ($this->testType != self::ACTION_TEST){
			throw new Exception("You are not allowed to use $method(), except in an action
					testing class.");
		}
		return true;
	}

	/**
	 * Returns a Selenium browser instance, creating one if it doesn't already exist (unless
	 * $createAnyway is false).
	 */
	private static function getBrowserInstance ($createAnyway = true) {
		static $browser;
		if (!isset($browser) && $createAnyway) {
			require('Testing/Selenium.php');
			$browser = new Testing_Selenium("*firefox", Config::get('webRoot'));
			$browser->start();
		}
		return $browser;
	}

	/**
	 * Stops the current browser instance if present. Should not be called explicitly; it will
	 * be called implicitly at the very end of script execution.
	 */
	public static function stopBrowserInstance () {
		$browser = self::getBrowserInstance(false);
		if ($browser !== null) {
			$browser->stop();
		}
	}
}
?>
