<?php
require(ROOT . '/lib/UploadFile.php');

/**
 * Interceptor handling file uploads. For each file uploaded a <code>UploadFile</code> object representing
 * the uploaded file is created.
 *
 * If the target action is <code>ModelAware</code> and its model is prepared, each file is set on the
 * model. If on the other hand the action is <code>UploadAware</code>, the files are instead set directly
 * on the action.
 * 
 * @version		$Id: UploadInterceptor.php 1510 2008-06-17 05:45:50Z anders $
 */
class UploadInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		if (!empty($_FILES)) {
			$action = $dispatcher->getAction();

			// Determine where we are to set the files
			if ($action instanceof ModelAware && is_object($action->model)) {
				$setOn = $action->model;
			}
			else if ($action instanceof UploadAware) {
				$setOn = $action;
			}

			foreach ($_FILES as $param => $file) {
				if ($file['error'] != UPLOAD_ERR_NO_FILE) {
					/*
					 * File was actually uploaded (although it can still have errors, which will be
					 * contained in the UploadFile). Create and assign the UploadFile.
					 */
					$setOn->$param = new UploadFile($file['name'], $file['tmp_name'], $file['error']);
				}
			}
		}

		return $dispatcher->invoke();
	}
}
?>
