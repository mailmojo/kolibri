<?php
/**
 * Data access object for the item model. DAOs must be named <model>Dao and be places in a
 * "dao"-directory within the models directory. Notice that placeholder for values in queries use 
 * ? placeholders when you supply an array of parameters, or :propertyName when supplying an
 * object. Escaping of values to protect from SQL injection is automatically done for you.
 */
class ItemDao {
	/**
	 * load() should be used to retrieve a single object.
	 */
	public static function load ($id) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			SELECT name, description, price, added, received
				FROM items
				WHERE name = ?
SQL;
		return $db->getObject('Item', $query, $id); // Gets one object of type Item
	}
	
	/**
	 * findAll() is here used to retrieve, well, all [relevant] objects.
	 */
	public static function findAll () {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			SELECT name, description, price, added, received
				FROM items
				ORDER BY added DESC
SQL;
		return $db->getObjects('Item', $query); // Gets objects of type Item in an array (empty if none)
	}

	/**
	 * insert() is called when you save() a model which is not yet saved.
	 */
	public static function insert ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			INSERT INTO items (name, description, price, added)
				VALUES (:name, :description, :price, date('now'))
SQL;
		return $db->query($query, $item);
	}

	/**
	 * update() is called when you save() a model which already exists in the database.
	 */
	public static function update ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			UPDATE items SET received = :received,
							 name = :name,
							 description = :description,
							 price = :price
						WHERE name = :name
SQL;
		return $db->query($query, $item);
	}

	/**
	 * You guessed it, called when you delete() an object.
	 */
	public static function delete ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			DELETE FROM items WHERE name = :name
SQL;
		return $db->query($query, $item);
	}
}
?>
