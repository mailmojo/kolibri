<?php
/**
 * This class is a proxy for the data access object of a model.
 *
 * A proxy is used in front of the data access object in order to automatically set any model results back
 * into the model proxy. This makes it possible to call <code>$model->objects->all()</code> and have the
 * results of the <code>all()</code> method in the data access object be set directly back in $model (the
 * proxy) without any intervention.
 *
 * @version		$Id: DataAccessProxy.php 1542 2008-08-12 18:46:42Z anders $
 */
class DataAccessProxy {
	/**
	 * The associated model proxy.
	 * @var ModelProxy
	 */
	private $modelProxy;

	/**
	 * Class name of the DAO we are proxying.
	 * @var string
	 */
	private $daoClass;

	/**
	 * Constructor.
	 *
	 * @param ModelProxy $modelProxy	Associated model proxy.
	 * @param string $daoClass			Class name of the DAO.
	 */
	public function __construct ($modelProxy, $daoClass) {
		import($daoClass, 'dao');
		$this->modelProxy	= $modelProxy;
		$this->daoClass		= $daoClass;
	}

	/**
	 * Imports the DAO we are proxying upon wakeup (i.e. if a proxied model is put in the session).
	 */
	public function __wakeup () {
		import($this->daoClass, 'dao');
	}

	/**
	 * Provides access to the underlying data access methods in the DAO. The methods are called statically.
	 *
	 * @param string $name		Name of the method to invoke.
	 * @param array $args		Array of arguments to pass along to the data access method.
	 * @param mixed				TRUE if an array or object was successfully retrieved, else the results as
	 *							returned from the data access method.
	 */
	public function __call ($name, $args) {
		$reflection = new ReflectionMethod($this->daoClass, $name);

		if (empty($args)) {
			$model = $this->modelProxy->extract();
			if (is_object($model)) { // Trenger egentlig ikke denne sjekken? Array gjør vel ingenting...
				$args = array($model);
			}
		}

		$result = $reflection->invokeArgs(null, $args);

		if (is_object($result)) {
			return Models::getModel($result);
		}

		return $result;
	}

	public function load ($id) {
		$result = $this->invokeCall($id, 'one');

		if (is_object($result)) {
			// Set the result back into the proxy
			$this->modelProxy->setModel($result);
		}

		return $result;
	}

	public function insert ($model) {
		$result = $this->invokeCall($model, 'insert');
		$pk = $model->pk();

		if (!is_bool($result) && is_scalar($result)) {
			$model->$pk = $result;
		}

		$model->original = $model->$pk;
		return $result;
	}

	public function update ($model) {
		$pk = $model->pk();
		if (empty($model->$pk)) {
			/*
			 * Actual PK property is empty, meaning that the PK is not being modified and we can thus
			 * safely set the PK value from the original property. This is not really *needed*, but the
			 * DAO can now refer to the PK property instead of the original property.
			 */
			$model->$pk = $model->original;
		}

		return $this->invokeCall($model, 'update');
	}
	
	public function delete ($model) {
		$pk = $model->pk();
		if (empty($model->$pk)) {
			$model->$pk = $model->original;
		}
		
		return $this->invokeCall($model, 'delete');
	}

	private function invokeCall ($param, $methodName) {
		$reflection = new ReflectionMethod($this->daoClass, $methodName);
		return $reflection->invoke(null, $param);
	}
}
?>
