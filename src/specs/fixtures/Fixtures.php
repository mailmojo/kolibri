<?php
/**
 * This class loads fixtures
 */
class Fixtures {
    /**
     * Deletes all the datafrom the test-table
     */
    public function __construct () { }
    
	public static function blankOutTable ($modelName) {
        $reflection = new ReflectionClass($modelName);
        $table = $reflection->getConstant('RELATION');
        
		$delete = "DELETE FROM $table";
        
        $db = DatabaseFactory::getConnection();
		$db->query($delete);
	}
	
    /**
     * Parses the <modelName>.ini file and returns an array of the objects.
     */
    public static function populate ($modelName) {
        if(!empty($modelName)) {
            $iniFile = APP_PATH . "/specs/fixtures/$modelName.ini";
            
            $models = parse_ini_file($iniFile, true);

            foreach($models as $name => $model) {
    
                if (is_array($model) && !empty($name)) {
                    $newModel = Models::init($modelName);
                    
                    foreach($model as $key => $value) {
                        $newModel->$key = $value;
                    }
                    
                    $stack[$name] = $newModel;
                    
                }
                
            }

        }
        return $stack;
    }
}
?>