<?php
/**
 * Kolibri Test framework
 */
class KolibriTestCase extends PHPSpec_Context {
    
    public $fixtures;
    public $modelName = null;
    
    public function before () {
        // blanks out the table for the given model.
        if($this->modelName) {
			$this->fixtures = new Fixtures($this->modelName);
			$this->fixtures->blankOutTable();
        }
        
        $this->preSpec();
    }
    
    /**
     * Executes before all spec methods is invoked. When it's a model test, it populates
     * <code>$fixtures</code> with data. There are currently no other modes available for testing.
     */
    public function beforeAll () {
        
		if (Config::getMode() != Config::TEST) {
			throw new Exception("KolibriTestCase requires that the current KOLBRI_MODE is set to TEST.");
		}
		
        $className = get_class($this);
        
        if (substr($className, -5) == 'Model') {
            $this->modelName = substr($className, 8, -5);
        }
        else if (substr($className, -6) == 'Action') {
            throw new Exception("KolibriTestCase doesn't support action testing yet.");
        }
        else if (substr($className, -6) == 'View') {
            throw new Exception("KolibriTestCase doesn't support view testing yet.");
        }
        else {
            throw new Exception("KolibriTestCase needs to have either Model, Action or View in the end of the classname");
        }
        
        $this->setup();
    }
    
    public function after () {
        $this->postSpec();
    }
    
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