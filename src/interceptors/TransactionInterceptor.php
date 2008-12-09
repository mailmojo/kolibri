<?php
/**
 * Interceptor which automatically commits/rolls back any active database transaction after the
 * action has returned.
 */
class TransactionInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		// Invoke next command in chain
		$result = $dispatcher->invoke();

		// XXX: Do we want to check the result of commit and give some message if rolled back?
		$db = DatabaseFactory::getConnection();
		$db->commit();
		return $result;
	}
}
?>
