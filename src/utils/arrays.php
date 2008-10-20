<?php
/**
 * Helper function related to arrays.
 *
 * @version 	$Id: arrays.php 1540 2008-08-06 23:52:44Z anders $
 */

function object_array_walk (&$array, $methodName) {
    foreach ($array as $obj) {
        $obj->$methodName();
    }
}

/**
 * BETA
 * Converts a multi-dimensional array into a one-dimensional array. Items which are objects are
 * converted to arrays consisting of their properties and considered just another dimension. An 
 * optional filter can be supplied, listing array keys in which should only be returned in the
 * flattened array.
 *
 * @param array $array	A multi-dimensional array to flatten into a one-dimensional array.
 * @param array $into	An array to fill with the flattened array, or <code>NULL</code> if the
 *						flattened array should be returned as usual.
 * @param array $filter	A list of array keys to only be concerned with, meaning all other keys
 *						in the original array will be discarded.
 * @return array	Unless an array for <code>$into</code> is supplied, the flattened array will
 *					be returned. Otherwise, nothing is returned.
 */
//function array_flatten ($array, &$into, $filter = null) {
//	if ($into === null) {
//		$into = array();
//		$should_return = true;
//	}
//
//	foreach ($array as $key => $value) {
//		if (empty($filter) || (!is_numeric($key) && in_array($key, $filter))) {
//			// No filter, or the active filter includes this key => add the value
//			if (is_object($value)) {
//				$properties = (method_exists($value, 'get_attributes') ?
//						$value->get_attributes() : get_object_vars($value));
//				array_flatten($properties, $into, $filter);
//			}
//			else if (is_array($value)) {
//				array_flatten($value, $into, $filter);
//			}
//			else {
//				if (is_numeric($key)) {
//					$into[] = $value;
//				}
//				else {
//					if (isset($into[$key])) {
//						if (!is_array($into[$key])) {
//							$into[$key] = array($into[$key]);
//						}
//
//						$into[$key][] = $value;
//					}
//					else {
//                      $into[$key] = $value;
//                    }
//				}
//			}
//		}
//		else if (is_object($value) || is_array($value)) {
//			$active_filter = (is_array($filter) && !is_numeric($key) && isset($filter[$key]) ?
//					$filter[$key] : $filter);
//			array_flatten($value, $into, $active_filter);
//		}
//	}
//
//	if (isset($should_return) && $should_return) {
//		return $into;
//	}
//}
?>
