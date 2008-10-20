<?php
/**
 * This class is a simple container to temporarily hold objects retrieved by
 * <code>ResultSet::fetch_objects()</code>.
 * 
 * This class doesn't extend <code>BaseModel</code> as we don't need any of the model functionality, but
 * we implement a simple <code>get_attributes()</code>-method to allow result data objects to be set into
 * this class.
 * 
 * @version 	$Id: Container.php 1523 2008-07-09 23:32:14Z anders $
 */
class Container {
	var $dataset = array();
	
	/**
	 * Returns an array indicating that this class' <code>$dataset</code> property can contain result
	 * data.
	 * 
	 * @return array
	 */
	function get_attributes () {
		return array('dataset' => '');
	}

	public function pk () { return null; }
}
?>
