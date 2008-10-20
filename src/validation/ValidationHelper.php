<?php
class ValidationHelper {
	public static function validateLength ($length, $rules) {
		$min = (isset($rules['minlength']) ? $rules['minlength'] : null);
		$max = (isset($rules['maxlength']) ? $rules['maxlength'] : null);
		$len = (isset($rules['length']) ? $rules['length'] : null);

		// Validate length of values (either min, max or exact)
		if ($min !== null && $length < $min) {
			return array('minlength' => array($rules['name'], $min));
		}
		else if ($max !== null && $length > $max) {
			return array('maxlength' => array($rules['name'], $max));
		}
		else if ($len !== null && $length != $len) {
			return array('length' => array($rules['name'], $len));
		}

		return true;
	}

	public static function validateSize ($size, $rules, $modifiedRules = null) {
		if ($modifiedRules !== null) {
			$min = (isset($modifiedRules['minsize']) ? $modifiedRules['minsize'] : null);
			$max = (isset($modifiedRules['maxsize']) ? $modifiedRules['maxsize'] : null);
		}
		else {
			$min = (isset($rules['minsize']) ? $rules['minsize'] : null);
			$max = (isset($rules['maxsize']) ? $rules['maxsize'] : null);
		}

		if ($min !== null && $size < $min) {
			// Only return min-message now if no max-rule is set, else we return general size-message below
			if ($max === null) {
				return array('minsize' => array($rules['name'], $rules['minsize']));
			}
		}
		else if ($max !== null && $size > $max) {
			// Only return max-message npw if no min-rule is set, else we return general size-message below
			if ($min === null) {
				return array('maxsize' => array($rules['name'], $rules['maxsize']));
			}
		}

		if ($min !== null && $max !== null && ($size < $min || $size > $max)) {
			// Both min- and max-rules are defined, so we return message referring to both conditions
			return array('size' => array($rules['name'], $rules['minsize'], $rules['maxsize']));
		}

		return true;
	}	
}
?>
