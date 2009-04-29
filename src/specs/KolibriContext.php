<?php
/**
 * This class is the Kolibri Test framework. It serves as an class for Model, Action and View
 * testing. Right now it support Action and Model testing. You have to extend KolibriContext
 * to use this test-framework. It reflects the same methods as PHPSpec_Context has, but they
 * are named differently. The corresponding method names are setup(), preSpec(), postSpec()
 * and tearDown().
 */
class KolibriContext extends PHPSpec_Context {
    public $fixtures;
    public $modelName = null;
    private $db = null;
	private $testType;
	
	const ACTION_TEST = 'action';
	const VIEW_TEST = 'view';
	const MODEL_TEST = 'model';
	
    /**
     * Executes before all spec methods are invoked. Distinguishes between model, action and view
     * testing. It also establishes a database connection.
     */
    public function beforeAll () {
		if (Config::getMode() != Config::TEST) {
			throw new Exception("KolibriTestCase requires that the current KOLBRI_MODE is set to TEST.");
		}
		
        $className = get_class($this);
		
        if (substr(strtolower($className), -5) == self::MODEL_TEST) {
			$this->testType = self::MODEL_TEST;
            $this->modelName = substr($className, 8, -5);
			$this->fixtures = new Fixtures($this->modelName);
        }
        elseif (substr(strtolower($className), -6) == self::ACTION_TEST) {
            $this->testType = self::ACTION_TEST;
        }
        elseif (substr(strtolower($className), -4) == self::VIEW_TEST) {
			$this->testType = self::VIEW_TEST;
            throw new Exception("KolibriContext does NOT support view testing yet.");
        }
        else {
            throw new Exception("KolibriTestCase needs to have either Model, Action or View in the end of the classname");
        }
        
		$this->db = DatabaseFactory::getConnection();
		if (method_exists($this, 'setup')) {
			$this->setup();
		}
    }
	
	/**
	 * Triggers the preSpec() method for doing something _before_ a spec has been invoked. 
	 */
    public function before () {
		$this->db->begin();
		if (method_exists($this, 'preSpec')) {
			$this->preSpec();
		}
    }

	/**
	 * Triggers the postSpec() method for doing something _after_ a spec. And it rolls back the current
	 * changes in the database.
	 */
    public function after () {
		$this->db->rollback();
		if (method_exists($this, 'postSpec')) {
			$this->postSpec();
		}
    }
    
	/**
	 * Triggers the tearDown() method for doing something _after_ every spec has runned. 
	 */
    public function afterAll () {
        unset($this->fixtures);
        unset($this->modelName);
		
		if (method_exists($this, 'tearDown')) {
			$this->tearDown();
		}

		unset($this->db);
		
		if (ob_get_level > 0) {
			ob_flush();
		}
    }
	
	
	/*
	 * Methods for Action testing
	 */
	public function get ($uri, array $params = null, array $session = null) {
		if ($this->validActionClass('get')) {
			$this->prepareEnvironment('GET', $uri, $session);
			$this->request = new Request($params !== null ? $params : array(), array());
			$this->fireRequest($this->request);
		}
	}

	public function post ($uri, array $params = null, array $session = null) {
		if ($this->validActionClass('post')) {
			$this->prepareEnvironment('POST', $uri, $session);
			$this->request = new Request(array(), $params !== null ? $params : array());
			$this->fireRequest($this->request);
		}
	}

	private function fireRequest ($request) {
		$rp = new RequestProcessor($request);
		$this->response = $rp->process(false);
		$this->action = $rp->getDispatcher()->getAction();
	}

	private function prepareEnvironment ($method, $uri, $session) {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
		$_SESSION = $session !== null ? $session : array();
	}
	
	/**
	 * Does not allow you to use post and/or get in any other testing classes than action.
	 *
	 * @param string $method method that are tested for
	 * @return bool returns true if its allowed to be used
	 */
	private function validActionClass ($method) {
		if ($this->testType != self::ACTION_TEST){
			throw new Exception("You are not allowed to use $method(), except in an action testing class.");
		}
		return true;
	}

}

?>