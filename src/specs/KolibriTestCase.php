<?php
/**
 * Kolibri Test framework
 */
class KolibriTestCase extends PHPSpec_Context {
    public $fixtures;
    public $modelName = null;
    private $db = null;
	
	/**
	 * Triggers the preSpec() method for doing something _before_ a spec has been invoked. 
	 */
    public function before () {
		$this->db->begin();
        $this->preSpec();
    }
    
    /**
     * Executes before all spec methods is invoked. When it's a model test, it populates
     * <code>$fixtures</code> with data.
     */
    public function beforeAll () {
		if (Config::getMode() != Config::TEST) {
			throw new Exception("KolibriTestCase requires that the current KOLBRI_MODE is set to TEST.");
		}
		
        $className = get_class($this);
        
        if (substr($className, -5) == 'Model') {
            $this->modelName = substr($className, 8, -5);
			$this->fixtures = new Fixtures($this->modelName);
        }
        elseif (substr($className, -6) == 'Action') {
            throw new Exception("KolibriTestCase doesn't support action testing yet.");
        }
        elseif (substr($className, -4) == 'View') {
            throw new Exception("KolibriTestCase doesn't support view testing yet.");
        }
        else {
            throw new Exception("KolibriTestCase needs to have either Model, Action or View in the end of the classname");
        }
        
		$this->db = DatabaseFactory::getConnection();
        $this->setup();
    }
    
	/**
	 * Triggers the postSpec() method for doing something _after_ a spec. And it rolls back the current
	 * changes in the database.
	 */
    public function after () {
		$this->db->rollback();
        $this->postSpec();
    }
    
	/**
	 * Triggers the tearDown() method for doing something _after_ every spec has runned. 
	 */
    public function afterAll () {
        unset($this->fixtures);
        unset($this->modelName);
		
        $this->tearDown();
    }
    
    /**
     * Functions for your testcase. acts the same as before/All() and after/All() in PHPSpec
     */
    public function setup () { }
    public function preSpec () { }
    public function postSpec () { }
	public function tearDown () { }
}

?>