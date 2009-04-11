<?php
/**
 * Action for adding new item. We implement ModelAware to have the model object populated by request 
 * data, and ValidationAware to have it automatically validated.
 */
class Add extends ActionSupport implements ModelAware, ValidationAware {
	/**
	 * Defines the model class to instantiate, which will be populated by request data and put back into 
	 * this variable.
	 */
	public $model = 'Item';

	/**
	 * Any validation errors are contained herein. If emtpy, the model is valid according to its rules.
	 */
	public $errors;

	/**
	 * As the name implies, this handles POST.
	 */
	public function doPost () {
		if (empty($this->errors)) {
			/*
			 * No validation errors are reported, so we can go ahead and save the model. Notice that $this->model 
			 * now is a fully prepared model.
			 */
			if ($this->model->save()) {
				$this->msg->setMessage('Item successfully added.');
			}
			return new RedirectResponse($this, '/');
		}

		/*
		 * Validation errors found, so return the page again to display errors with the form populated. If we 
		 * redirect, error messages and form data will be lost.
		 */
		return new XsltResponse($this, '/index');
	}
}
?>
