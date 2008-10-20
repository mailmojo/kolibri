<?php
/**
 * This interface is used by models that allow validation of its data. Validation rules is specified by
 * implementing the <code>rules()</code>-method which must return an array following this contract:
 * 
 * <ul>
 * <li>Each key specifies the validation rules of the corresponding model property.</li>
 * <li>Each value must be another array where the first element is a bit mask using the validation
 * constants that define the actual validation rules for the property.</li>
 * <li>Validation properties can be specified as key-value pairs within the value-array. The property
 * <em>name</em> is common to all validators, else they vary depending on the validator used by the
 * rules.</li>
 * </ul>
 * 
 * @version		$Id: Validateable.php 1492 2008-04-29 23:57:42Z anders $
 */
interface Validateable {
	/**
	 * Returns the validation rules for this model.
	 *
	 * @return array	Validation rules.
	 */
	public function rules ();
}
?>
