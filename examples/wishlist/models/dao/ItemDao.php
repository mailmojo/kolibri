<?php
/**
 * Data access object for the item model. DAOs must be named <model>Dao and be places in a "dao"-directory in
 * the models directory. Notice that placeholder for values in queries use the standard sprintf()
 * placeholders. Escaping of values to protect from SQL injection is automatically done for you.
 */
class ItemDao {
	/**
	 * one() should be used to retrieve a single object.
	 */
	public static function one ($id) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			SELECT name, description, price, added, received
				FROM items
				WHERE name = %s
SQL;
		return $db->getObject('Item', $query, array($id)); // Gets one object of type Item
	}

	/**
	 * all() should be used to retrieve, well, all [relevant] objects.
	 */
	public static function all () {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			SELECT name, description, price, added, received
				FROM items
				ORDER BY added DESC
SQL;
		return $db->getObjects('Item', $query); // Gets objectS of type Item in an array (empty if none)
	}

	/**
	 * insert() is called when you save() a model which is not yet saved.
	 */
	public static function insert ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			INSERT INTO items (name, description, price, added) VALUES (%s, %s, %s, date('now'))
SQL;
		return $db->exec($query, array($item->name, $item->description, $item->price));
	}

	/**
	 * update() is called when you save() a model which already exists in the database.
	 */
	public static function update ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			UPDATE items SET received = %s WHERE name = %s
SQL;
		return $db->exec($query, array($item->received, $item->name));
	}

	/**
	 * You guessed it, called when you delete() an object.
	 */
	public static function delete ($item) {
		$db = DatabaseFactory::getConnection();
		$query = <<<SQL
			DELETE FROM items WHERE name = %s
SQL;
		return $db->exec($query, array($item->name));
	}
}
?>
