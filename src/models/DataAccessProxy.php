<?php
/**
 * This class is a proxy for the data access object of a model.
 *
 * A proxy is used in front of the data access object in order to automatically set the primary key
 * on the model, and set any model results from queries back into the model proxy. This makes it
 * possible to call <code>$model->objects->findAll()</code> and have the results of the
 * <code>findAll()</code> method in the data access object be set directly back in $model (the
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
	 * Name of the primary key property of the model type we are proxying.
	 * @var string
	 */
	private $modelPk;

	/**
	 * Constructor.
	 *
	 * @param ModelProxy $modelProxy Associated model proxy.
	 * @param string $modelClass     Class name of the model we are proxying.
	 */
	public function __construct ($modelProxy, $modelClass) {
		$this->modelProxy	= $modelProxy;
		$this->daoClass		= $modelClass . 'Dao';
		import($this->daoClass, 'dao');

		$reflection = new ReflectionClass($modelClass);
		$this->modelPk = $reflection->getConstant('PK');
	}

	/**
	 * Imports the DAO we are proxying upon wakeup (i.e. if a proxied model is put in the session).
	 */
	public function __wakeup () {
		import($this->daoClass, 'dao');
	}

	/**
	 * Proxies <code>load()</code> to set the result back into the model proxy before. The result
	 * as-is is returned. This makes it possible to do this to populate a $user proxy:
	 *
	 *   $user->objects->load($someId);
	 *
	 * As well as the following if you simply want a plain model object.
	 *
	 *   $notProxiedModel = $user->objects->load($someId);
	 *
	 * @param mixed $id The id needed to load the model. Passed as-is, so could even be an array.
	 * @return mixed    The results as returned from the DAO method.
	 */
	public function load ($id) {
		$result = $this->invokeCall($id, 'load');

		if (is_object($result)) {
			// Set the result back into the proxy
			$this->modelProxy->setModel($result);
		}
		return $result;
	}

	/**
	 * Proxies <code>insert()</code> to populate the primary key and original properties in the model
	 * after the DAO has been called.
	 *
	 * @param object $model The model to pass to the DAO method.
	 * @return mixed        The results as returned from the DAO method.
	 */
	public function insert ($model) {
		$result = $this->invokeCall($model, 'insert');
		$this->setPrimaryKey($model, $result->lastInsertId());
		$model->original = $model->{$this->modelPk};
		return $result;
	}

	/**
	 * Proxies <code>update()</code> to populate the primary key property from the original property
	 * if not already set, before calling the DAO.
	 *
	 * @param object $model The model to pass to the DAO method.
	 * @return mixed        The results as returned from the DAO method.
	 */
	public function update ($model) {
		$this->setPrimaryKey($model, $model->original);
		return $this->invokeCall($model, 'update');
	}
	
	/**
	 * Proxies <code>delete()</code> to populate the primary key property from the original property
	 * if not already set, before calling the DAO.
	 *
	 * @param object $model The model to pass to the DAO method.
	 * @return mixed        The results as returned from the DAO method.
	 */
	public function delete ($model) {
		$this->setPrimaryKey($model, $model->original);
		return $this->invokeCall($model, 'delete');
	}

	/**
	 * Provides access to the underlying data access methods in the DAO. The methods are called
	 * statically, and the results returned as-is.
	 *
	 * @param string $name Name of the method to invoke.
	 * @param array $args  Array of arguments to pass along to the data access method.
	 * @return mixed       The results as returned from the DAO method.
	 */
	public function __call ($name, $args) {
		$reflection = new ReflectionMethod($this->daoClass, $name);

		/*
		 * If no arguments were supplied, we pass the model(s) on as arguments, so we don't have
		 * to do this manually ($model->objects->something($model) looks kind of strange).
		 */
		if (empty($args)) {
			$model = $this->modelProxy->extract();
			$args = array($model);
		}

		return $reflection->invokeArgs(null, $args);

		// XXX: We did this previously, but unsure if we need to any more. ModelProxy should be able
		// to convert when needed.
		//if (is_object($result)) {
		//	return Models::getModel($result);
		//}
	}

	/**
	 * Sets the primary key property on the model to the supplied value, if and only if the property
	 * is empty.
	 *
	 * @param object $model Model to set primary key on.
	 * @param mixed $value  Value to set primary key to.
	 */
	private function setPrimaryKey ($model, $value) {
		if (empty($model->{$this->modelPk})) {
			$model->{$this->modelPk} = $value;
		}
	}

	/**
	 * Invokes the a method with a single parameter on the data access object, and returns the result
	 * from the call.
	 *
	 * @param mixed $param       Parameter to supply to the method.
	 * @param string $methodName Name of the method to invoke.
	 * @return mixed             The returned result from the method.
	 */
	private function invokeCall ($param, $methodName) {
		$reflection = new ReflectionMethod($this->daoClass, $methodName);
		return $reflection->invoke(null, $param);
	}
}
?>
