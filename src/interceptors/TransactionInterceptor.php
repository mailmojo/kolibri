<?php
/**
 * Interceptor which automatically handles database transactions for POST requests.
 *
 * If the request is a POST we assume that the request may modify the database, and we therefore start 
 * a transaction. During post-processing, if no error is reported on the database connection we commit the 
 * transaction, else we roll it back.
 * 
 * @version		$Id: TransactionInterceptor.php 1515 2008-06-30 13:07:35Z anders $
 */
class TransactionInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$db = DatabaseFactory::getConnection();

		// Invoke next command in chain
		$result = $dispatcher->invoke();

		if ($db->commit() === false) {
			$action = $dispatcher->getAction();
			if ($action instanceof MessageAware) {
				$action->msg->setMessage('En feil oppstod under behandlingen av din forespørsel.', false);

				// TODO: Re-implement error reporting
				/*if (Config::get('debug')) {
					$action->msg->addDetail($db->getLastError());
					$action->msg->addDetail('QUERY: ' . $db->getLastQuery());
				}*/
			}
		}

		return $result;
	}
}
?>
