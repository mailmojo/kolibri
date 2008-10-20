<?php
/**
 * This interface is used by classes that allow exposure of its state to a result. The interface
 * has no methods or fields, and serves only to identify exposable classes. Classes that require
 * other semantics than the default (which is simply to expose all public fields) may implement the
 * following method:
 *
 *    public function expose ()
 *
 * The <code>expose()</code>-method must return an associative array of the fields it wishes to expose.
 * 
 * @version		$Id: Exposable.php 1492 2008-04-29 23:57:42Z anders $
 */
interface Exposable {}
?>
