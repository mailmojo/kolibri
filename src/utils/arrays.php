<?php
/**
 * Helper functions related to arrays. Mostly variants of native PHP functions, and thus
 * the function names and parameters are kept similar.
 */

/**
 * Loops through an array of objects and invokes a method on each one of them.
 *
 * @param array $array       Array with objects.
 * @param string $methodName Name of object method to invoke.
 */
function object_array_walk (array &$array, $methodName) {
	foreach ($array as $obj) {
		$obj->$methodName();
	}
}

/**
 * Loops through an array of objects, invokes a method on each one of them and assigns
 * the return value to a new array (while keeping keys the same).
 *
 * @param string $methodName Name of object method to invoke.
 * @param array $array       Array with objects.
 * @return array Array with return values from invoking the method on each object.
 */
function object_array_map ($methodName, array $array) {
	$newArray = array();
	foreach ($array as $key => $obj) {
		$newArray[$key] = $obj->$methodName();
	}
	return $newArray;
}

/**
 * A blend of PHP's built-in array_merge() and array_merge_recursive(). This function merges
 * recursively for array values, but like array_merge() it will overwrite a value in the
 * first array with the corresponding value from the second array for equal keys.
 *
 * @param array $first  Base array in which values will be merged into.
 * @param array $second Array to merge values from.
 * @param array …       Optional extra arrays to merge values from.
 * @return array Array with merged values from all input arrays.
 */
function array_merge_recursive_distinct (array $first, array $second/*, array …*/) {
	$merged = $first;

	foreach ($second as $key => $value) {
		if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
			$merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
		}
		else {
			$merged[$key] = $value;
		}
	}

	if (func_num_args() > 2) {
		$params = array_merge(array($merged), array_slice(func_get_args(), 2));
		return call_user_func_array('array_merge_recursive_distinct', $params);
	}
	
	return $merged;
}
?>
