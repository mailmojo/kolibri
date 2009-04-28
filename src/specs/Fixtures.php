<?php
/**
 * This class loads fixtures for our test-framework. It acts like an array, so you can
 * access all your fixture by using <code>$this->fixture['fixtureName']</code>. Each
 * fixture is loaded as their correct model, so you can call the method <code>save()</code>
 * etc, when ever you like.
 */
class Fixtures implements ArrayAccess {
	private $fixtures = array();
	private $modelName = null;
	
	/**
	 * Creates a new instance of this class. The modelName will be saved for usage in the
	 * <code>populate()</code> method wich is executed right away to set up the fixtures.
	 *
	 * @param string $modelName Name of the model.
	 */
	public function __construct($modelName) {
		$this->modelName = $modelName;
		$this->populate();
	}
	
    /**
     * Parses the <modelName>.ini file and returns an array of the objects.
     */
    public function populate () {
        if ($this->modelName) {
            $iniFile = APP_PATH . "/specs/fixtures/$this->modelName.ini";
            
			if (!file_exists($iniFile)) {
				return null;
			}

            $models = parse_ini_file($iniFile, true);
            foreach ($models as $name => $model) {
                if (is_array($model) && !empty($name)) {
                    $newModel = Models::init($this->modelName);
                    
                    foreach ($model as $key => $value) {
                        $newModel->$key = $value;
                    }
                    $this->fixtures[$name] = $newModel;
                }
			}
        }
    }
	
	/**
	 * Methods needed for ArrayAccess
	 */
    public function offsetSet ($offset, $value) {
        $this->fixtures[$offset] = $value;
    }
    public function offsetExists ($offset) {
        return isset($this->fixtures[$offset]);
    }
    public function offsetUnset ($offset) {
        unset($this->fixtures[$offset]);
    }
    public function offsetGet ($offset) {
        return isset($this->fixtures[$offset]) ? $this->fixtures[$offset] : null;
    }
}
?>