<?php
/**
 * This interface is used by actions that want to control access based on the role of users.
 *
 * @version		$Id: AuthAware.php 1515 2008-06-30 13:07:35Z anders $
 */
interface AuthAware {
	/**
	 * Returns an array with strings of the roles that are allowed access to this action.
	 *
	 * @return array	Allowed roles.
	 */
//	public function allowedRoles ();

	/**
	 * Called when access is denied due to the user not having an allowed role. This gives the action
	 * the opportunity to forward the user to whatever location or view desired.
	 *
	 * @return Result	A result to render to the denied user.
	 */
//	public function denyAccess ();
}
?>
