<?php
/**
 * Model class for a wishlist item. Implemented interfaces tells the model framework that this model can be 
 * validated, is database backed and can be exposed to views.
 */
class Item implements Validateable, DataProvided, Exposable {
	const PK = 'name';
	public $name;
	public $description;
	public $price;
	public $added;
	public $received;

	/**
	 * Defines the validation rules for this model. See conf/validation.php in Kolibri for even more validators.
	 */
	public function rules () {
		return array(
			// Name is required (EXISTS), must be text (not binary, IS_TEXT) and unique within the table (UNIQUE)
			'name'        => array(EXISTS | IS_TEXT, 'name' => 'Item name'),
			// If we wanted to we could restrict length by: 'maxlength' => number
			'description' => array(IS_TEXT, 'name' => 'Description'),
			// We could likewise restrict upper price bounds as well, with 'maxsize'
			'price'       => array(IS_NUM, 'minsize' => 1, 'name' => 'Price')
		);
	}

	// Note: following function will be converted to class constants aka PK in the future
	/**
	 * Defines the relation this model gets its data from. Is currently only used by the UNIQUE validation
	 * rule.
	 */
	public function relation () {
		return 'items';
	}
}
?>
