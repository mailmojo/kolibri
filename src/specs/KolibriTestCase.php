<?php
define(ENVIRONMENT, 'test');

require('TestCaseRequires.php');

class KolibriTestCase extends PHPSpec_Context {
    
    public $fixtures;
    public $modelName;
    
    public function before () {
        
        // blanks out the table for the given model.
        if(!empty($this->modelName)) {
            Fixtures::blankOutTable($this->modelName);
        }
        
        $this->infront();
    }
    
    /**
     * populates fixtures from the specs/fixtures/<ModelName>.ini file
     */
    public function beforeAll () {
        
        $className = get_class($this);
        
        if (substr($className, -5) == 'Model') {
            $this->modelName = substr($className, 8, -5);
            
            // TODO: hmm..
            import($this->modelName);
            
            $this->fixtures = Fixtures::populate($this->modelName);
        }
        else if (substr($className, -6) == 'Action') {
            // action testing
        }
        else {
            throw new Exception("KolibriTestCase needs to have either Model or Action in the end of the classname");
        }
        
        $this->setup();
    }
    
    public function after () {
        $this->tearDown();
    }
    
    public function afterAll () {
        unset($this->fixtures);
        
        $this->tearDownLast();
    }
    

    /**
     * Functions for the TestCase. These metohds are not abtsract because the
     * TestCase class does not need have them in there.
     */
    public function setup () { }
    public function infront () { }
    public function tearDown () { }
    public function tearDownLast () { }
    

	
	
}

?>