<?php
/**
 * Action for adding new item. We implement ModelAware to have the model object populated by
 * request data, and ValidationAware to have it automatically validated.
 */
class Add extends ActionSupport implements ModelAware, ValidationAware {
	/**
	 * Defines the model class to instantiate, which will be populated with request data and
	 * put back into this property.
	 */
	public $model = 'Item';

	/**
	 * As the name implies, this handles POST. It will only be called if the model validates,
	 * else validationFailed() will be called instead.
	 */
	public function doPost () {
		$this->model->save();
		$this->msg->setMessage('Item successfully added.');
		return new RedirectResult('/');
	}

	/**
	 * This is called when validation fails, in order for us to redirect back to where the
	 * form is presented. By using redirect instead of simply displaying the form now,
	 * we conform to the Post-Redirect-Get webapp pattern, which among other things lets
	 * users safely go Back/Forward and Refresh.
	 */
	public function validationFailed () {
		// We could set a custom error message here if we want to override the default. I.e.:
		// $this->msg->setMessage('The item could not be added to the wishlist', false);
		return new RedirectResult('/');
	}
}
?>
