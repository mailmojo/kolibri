<?php
/**
 * Provides base functionality for all models.
 * 
 * @version		$Id: BaseModel.php 892 2007-01-15 07:06:25Z anders $
 */
class BaseModel {
	/**
	 * The name of the attribute(s) that defines the primary key of this model. If more than one
	 * attribute defines the primary key, an array of attribute names should be defined.
	 * @var mixed
	 */
	var $pk;
	
	/**
	 * Original entity of this model. Should be set if the primary key value of this instance has been
	 * changed before an update() will be issued, to ensure that the correct entity is updated in the
	 * database.
	 * 
	 * TODO: Change to $pk_val, and keep it up-to-date automatically. (No real need to store complete object.)
	 * 
	 * @var mixed
	 */
	var $original;
	
	/**
	 * The ActionHandler who created this model.
	 * @var ActionHandler
	 */
	var $handler;
	
	/**
	 * Database connection facility.
	 * @var DatabaseConnection
	 */
	var $db;
	
	/**
	 * Constructor initializing availible resources.
	 *
	 * @return BaseModel
	 */
	function BaseModel () {
		$this->_init_class();
	}
	
	/**
	 * Wakes up this class when being unserialized (for instance when loading from a session).
	 */
	function __wakeup () {
		$this->_init_class();
	}
	
	/**
	 * Makes sure request specific data does not get serialized and saved in the session.
	 *
	 * @return array Names of the instance variables that are allowed to be saved in a session.
	 */
	function __sleep () {
		return array_keys($this->get_attributes());
	}
	
	/**
	 * Sets the action handler which initialized this model.
	 * 
	 * @param &$handler		Action handler.
	 */ 
	function set_action_handler (&$handler) {
		$this->handler =& $handler;
	}
	
	/**
	 * Returns the primary key value of this model. Composite primary key values are returned in an array,
	 * unless <code>$collapse</code> is set to <code>TRUE</code>. In that case, a string with the values
	 * concatenated by a <code>+</code> character is returned.
	 *
	 * @return mixed	Primary key value of this model.
	 */
	function pk ($collapse = false) {
		if (empty($this->pk)) {
			return null;
		}
		
		if (!is_array($this->pk)) {
			return $this->{$this->pk};
		}
		
		foreach ($this->pk as $key) {
			$pk[] = $this->$key;
		}
		
		// If collapse composite PK, implode with + separating the values
		return (!$collapse ? $pk : implode('+', $pk));
	}
	
	/**
	 * Defines and returns the validation rules for this model, or NULL if no validation should
	 * be done.
	 * 
	 * @return array	Validation rules, or <code>NULL</code>.
	 */ 
	function rules () {
		return null;
	}
	
	/**
	 * Checks to see if this model equals another. They are considered equal if they are of the
	 * same type, and their primary keys have the same value. If no primary key is identified for this
	 * model, every attribute will be compared for equality to determine whether the models are equal.
	 * 
	 * @param object $other		The model to compare this to.
	 * @return bool		<code>TRUE</code> if this model equals <code>$other</code>, else
	 * 					<code>FALSE</code>.
	 */ 
	function equals ($other) {
		// First check class type
		if (get_class($this) != get_class($other)) {
			return false;
		}
		
		// If model has identified its primary key, use it for comparison
		if (!empty($this->pk)) {
			if (!is_array($this->pk)) {
				return ($this->{$this->pk} == $other->{$this->pk});
			}
			else {
				foreach ($this->pk as $key) {
					if ($this->$key != $other->$key) {
						return false;
					}
				}
				return true;
			}
		}
		
		// No primary key identified, compare complete object
		if (version_compare(PHP_VERSION, '5.0.0') >= 0) { // PHP 5.0.0 or newer, supports direct comparison
			return $this == $other;
		}
		
		$other_props = $other->get_attributes();
		$this_props = $this->get_attributes();
		if (count($this_props) != count($other_props)) {
			return false;
		}
		
		foreach ($this_props as $prop => $val) { // Loop through and check property names and values
			if (!array_key_exists($prop, $other_props)) {
				return false;
			}
			if ($val != $other_props[$prop]) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Validates this model according to the rules specified by <code>rules()</code>. If no
	 * validator has been configured or no rules have been specified, no validation is done.
	 * Any validation errors encountered are stored in the request parameter
	 * <code>validation-errors</code>. 
	 * 
	 * @param array $special_rules	Extra rules which may override the models default rules.
	 * @return bool	<code>TRUE</code> if the model validated successfully, <code>FALSE</code>
	 * 				on failures.
	 */
	function validate ($special_rules = null) {
		if (!empty($this->handler->validator)) {
			$results = call_user_func($this->handler->validator, $this, $special_rules);
			if (!empty($results)) {
				$this->handler->request->put('validation-errors', $results);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * A general method for other objects to get the representational attributes of this model.
	 * By default all the model's variables are returned.
	 *
	 * @return array	An associative array with the models representational attributes, using the
	 *					attribute's name as array index.
	 */
	function get_attributes () {
		$properties = get_object_vars($this);
		
		// These properties should never be exposed
		unset($properties['handler']);
		unset($properties['db']);
		return $properties;
	}
	
	/**
	 * Convenience method to initialize this class, whether it is loaded by the constructor or
	 * <code>__wakeup()</code>.
	 */
	function _init_class () {
		require_once('database/DatabaseConnection.php');
		$this->db =& DatabaseConnection::get_instance();
	}
}
?>
