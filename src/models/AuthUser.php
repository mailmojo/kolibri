<?php
class AuthUser {
	public $username;
	public $roles = array();

	public function __construct ($username) {
		$this->username = $username;
	}

	public function hasRole ($roles) {
		if (is_array($roles)) {
			foreach ($roles as $role) {
				if (in_array($role, $this->roles)) {
					return true;
				}
			}
		}
		else {
			if (in_array($roles, $this->roles)) {
				return true;
			}
		}

		return false;
	}
}
?>
