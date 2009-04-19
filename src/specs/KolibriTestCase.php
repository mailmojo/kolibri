<?php
/**
 * Kolibri Test framework
 */
class KolibriTestCase extends PHPSpec_Context {
    
    public $fixtures;
    public $modelName;
    
    public function before () {
        // blanks out the table for the given model.
        if($this->modelName) {
            Fixtures::blankOutTable($this->modelName);
        }
        
        $this->preSpec();
    }
    
    /**
     * populates fixtures from the specs/fixtures/<ModelName>.ini file
     */
    public function beforeAll () {
        
        $className = get_class($this);
        
        if (substr($className, -5) == 'Model') {
            if($this->modelName = substr($className, 8, -5)) {
				$this->fixtures = Fixtures::populate($this->modelName);
			} 
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