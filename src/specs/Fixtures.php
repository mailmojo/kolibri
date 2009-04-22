<?php
/**
 * This class loads fixtures. Acts as an array
 */
class Fixtures implements ArrayAccess {
	
	private $fixtures = array();
	private $modelName = null;
	
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