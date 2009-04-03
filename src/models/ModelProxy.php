<?php
require(ROOT . '/models/DataAccessProxy.php');

/**
 * This class is a proxy for model objects, which adds functionality that makes it easier to work
 * with models.
 *
 * If the model we are proxying is <code>DataProvided</code> a <code>DataAccessProxy</code> is
 * instantiated and made available through the <code>objects</code> property. This makes it easy to
 * access the model's data access object through a consistent interface. This also adds additional
 * functionality such as automatic handling of <code>save()</code>-ing og <code>delete()</code>-ing
 * regardless of how many instances of the model is contained in this proxy.
 
 * For instance, you can <code>$model->objects->findAll()</code>, do some processing on one or more
 * of the instances before calling <code>$model->save()</code> to persist the changes to the
 * underlying storage.
 *
 * As a proxy can contain any number of model instances, a concept of <em>current model</em> is
 * used. This indicates the model at the position of the internal array pointer, which you should
 * not rely on for access to a specific model unless you know only one model instance is held. What
 * this means is that any methods that works on the <em>current model</em> should only be called
 * when you have only instantiated or retrieved a single model instance.
 * 
 * This class also implements <code>ArrayAccess</code> and <code>IteratorAggregates</code> which
 * makes it possible to treat the collection of models in the proxy as if it was a regular array.
 */
class ModelProxy implements ArrayAccess, IteratorAggregate, Countable, Proxy {
	/**
	 * Provides access to the data access object of the model.
	 * @var DataAccessProxy
	 */
	public $objects;

	/**
	 * One or more model instances we are proxying.
	 * @var array
	 */
	protected $models;

	/**
	 * Current model.
	 * @var object
	 */
	protected $current;

	/**
	 * Creates a <code>ModelProxy</code> instance for the model supplied.
	 *
	 * @param object $model Model to proxy.
	 */
	public function __construct ($model) {
		if (is_object($model)) {
			$this->setModel($model);
		}
		else { // Is array
			$this->setModels($model);
			// Set $model to one of the contained models so we can init the correct DAO proxy
			$model = current($this->models);
		}

		$this->initDaoProxy($model);
	}

	/**
	 * Iterates the contained models and updates dirty models or inserts new models. The number of
	 * actually saved rows in the database is returned.
	 *
	 * @return int Number of saved rows in the database.
	 */
	public function save () {
		$numAffected = 0;

		if (isset($this->objects)) {
			foreach ($this->models as $idx => $model) {
				$proceed = true;

				// If model has preSave()-method, call it to determine if we should continue
				// XXX: Should this be here, or is it more appropriate in DataAccessProxy?
				if (method_exists($model, 'preSave')) {
					$proceed = $model->preSave();
				}

				if ($proceed !== false) {
					if (!empty($model->original)) {
						if (property_exists($model, 'isDirty') && $model->isDirty) {
							$numAffected += $this->objects->update($model);
						}
					}
					else {
						$numAffected += $this->objects->insert($model);
					}
				}

				// Loop through model properties and save any inner models
				foreach ($model as $property => &$innerModel) {
					/*
					 * If $innerModel is an array or object, we try to proxy it. If it succeeds, it is
					 * indeed one or more models we might want to save below, so we put the proxy back
					 * into the model object.
					 */
					if (is_array($innerModel) || is_object($innerModel)) {
						$proxy = Models::getModel($innerModel);
						if ($proxy !== null) {
							$innerModel = $proxy;
						}
					}

					if ($innerModel instanceof ModelProxy) {
						$this->propagateKey($innerModel, $model);
						// XXX: We do nothing with the number of affected rows for inner saves. Should we?
						$innerModel->save();
					}
				}

				// The model might have been updated or inserted, so set as not-dirty regardless
				$model->isDirty = false;
			}
		}

		return $numAffected;
	}

	/**
	 * If $key is specified, the contained model at that index is deleted from the database and
	 * removed from this proxy. Else all contained models are iterated and deleted from the database.
	 *
	 * The number of actually deleted in the database is returned.
	 *
	 * @param string $key	Key of one model to delete.
	 * @return int			Number of deleted rows in the database.
	 */
	public function delete ($key = null) {
		$numAffected = 0;

		if (isset($this->objects)) {
			if (!empty($key)) {
				if (isset($this->models[$key])) {
					$numAffected = $this->objects->delete($this->models[$key]);
					unset($this->models[$key]);
				}
				else throw new Exception("Model with key $key does not exist.");
			}
			else {
				foreach ($this->models as $idx => $model) {
					if (!empty($model->original)) {
						$numAffected += $this->objects->delete($model);
					}
					unset($this->models[$idx]);
				}
			}
		}

		return $numAffected;
	}

	/**
	 * Checks if the specified property on the current model is empty.
	 *
	 * This is a magic method triggered by calling <code>isset()</code> or empty()</code> on a property.
	 *
	 * @param string $name Name of the property to check.
	 * @return bool        <code>TRUE</code> if the property is empty within the current model,
	 *                     <code>FALSE</code> if not.
	 */
	public function __isset ($name) {
		return !empty($this->current->$name);
	}

	/**
	 * Retrieves a specific property on the current model.
	 *
	 * This is a magic method triggered by trying to access an inaccessible property, i.e. a property
	 * of the enclosed model.
	 *
	 * @param string $name Name of the property to retrieve.
	 * @return mixed       Value of the property within the current model, or <code>NULL</code> if
	 *                     undefined.
	 */
	public function &__get ($name) {
		if (isset($this->current->$name)) {
			return $this->current->$name;
		}
		$null = null;
		return $null;
	}

	/**
	 * Sets a specific property on the current model.
	 *
	 * This is a magic method triggered by trying to set an inaccessible property, i.e. a property
	 * of the enclosed model.
	 *
	 * @param string $name Name of the property to set.
	 * @param mixed $value Value to set on the property.
	 */
	public function __set ($name, $value) {
		foreach ($this->models as $idx => $model) {
			if (property_exists($model, $name)) {
				if ($model->$name !== $value) {
					$model->$name = $value;
					$model->isDirty = true;
				}
			}
		}
	}

	/**
	 * Invokes a specific method on the current model. If any arguments are supplied, we assume the
	 * model state will be changed, and we therefore set it as dirty.
	 *
	 * @param string $name Name of the method to invoke.
	 * @param array $args  Arguments to pass to the method.
	 * @return mixed       The value as returned from the model method.
	 */
	public function __call ($name, $args) {
		if (!empty($args)) {
			$this->current->isDirty = true;
		}

		$reflection = new ReflectionMethod(get_class($this->current), $name);
		return $reflection->invokeArgs($this->current, $args);
	}

	/**
	 * Checks if a model exists at the specified offset.
	 *
	 * @param mixed $offset Array offset to check.
	 * @return bool         <code>TRUE</code> of the offset is set, <code>FALSE</code> if not.
	 */
	public function offsetExists ($offset) {
		return isset($this->models[$offset]);
	}

	/**
	 * Retrieves the model at the specified offset, or NULL if not set.
	 *
	 * @param mixed $offset Array offset to retrieve model from.
	 * @return mixed        A model if found at $offset, else <code>NULL</code>.
	 */
	public function offsetGet ($offset) {
		return (isset($this->models[$offset]) ? $this->models[$offset] : null);
	}

	/**
	 * Sets a value at the specified offset in the model array. This should rarely (if ever) be called, and
	 * only with an actual model as the value.
	 *
	 * @param mixed $offset Array offset to set.
	 * @param mixed $value  Value to set at the array offset.
	 */
	public function offsetSet ($offset, $value) {
		if (is_object($value)) {
			if ($offset !== null) {
				$this->models[$offset] = $value;
				$this->models[$offset]->isDirty = true;
			}
			else {
				$this->models[] = $value;
			}
		}
	}

	/**
	 * Unsets the value at the specified offset.
	 *
	 * @param mixed $offset Array offset to unset.
	 */
	public function offsetUnset ($offset) {
		if (isset($this->models[$offset])) {
			unset($this->models[$offset]);
		}
	}

	/**
	 * Returns a default iterator which enables iterating over models.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator () {
		return new ArrayIterator($this->models);
	}

	/**
	 * Returns a count of the contained models.
	 *
	 * @return int
	 */
	public function count () {
		return count($this->models);
	}

	/**
	 * Sets a single model contained within this proxy, and sets it as the current model. Should rarely
	 * be called manually.
	 *
	 * @param object $model The model to set on this proxy.
	 */
	public function setModel ($model) {
		$this->models = array($model);
		$this->current = $this->models[0];
	}

	/**
	 * Sets the models contained within this proxy. Should rarely be called manually, as the use of
	 * <code>objects</code> does this for you.
	 *
	 * @param array $models Array of one or more models to set on this proxy.
	 */
	public function setModels ($models) {
		if (is_array($models)) {
			$this->models = $models;
			$this->current = null;
		}
	}

	/**
	 * Extracts the models contained in this proxy. This is useful when you want to pass the models
	 * onto some other model or facility without the proxy functionality.
	 *
	 * @return mixed The contained model (if one) or array of models (if several).
	 */
	public function extract () {
		if (isset($this->current)) {
			return $this->current;
		}
		return $this->models;
	}

	/**
	 * Initializes a proxy to the data access object of the model, if it is <code>DataProvided</code>.
	 *
	 * @param object $model A model we are proxying.
	 */
	private function initDaoProxy ($model) {
		if ($model instanceof DataProvided) {
			$this->objects = new DataAccessProxy($this, get_class($model));
		}
	}

	/**
	 * Checks every model object in a ModelProxy for the existance of a foreign key to the supplied model
	 * and updates it's value if it's empty.
	 *
	 * @param ModelProxy $proxy The ModelProxy with model objects to update.
	 * @param object $model     The model whose primary key defines the foreign key to look for.
	 */
	private function propagateKey ($proxy, $model) {
		$reflection = new ReflectionObject($model);
		$pk = $reflection->getConstant('PK');

		foreach ($proxy as $innerModel) {
			if (property_exists($innerModel, $pk) && empty($innerModel->$pk)) {
				// Inner model has an empty foreign key to the main model, initialize before saving
				$innerModel->$pk = $model->$pk;
			}
		}
	}
}
?>
