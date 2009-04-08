<?php
/**
 * Interface to indicate that an object is a proxy for one or more objects.
 * When a proxy proxies only one object, it's contents should not be treated
 * as an array of objects. Even though iterating over the proxy will mean iterating
 * over an array of one or more objects.
 * See XmlGenerator for an example of how a single object proxy has it's proxied object extracted
 * before iterating over properties, instead of iterating over the proxy itself directly.
 */
interface Proxy {
	public function extract ();
}
?>