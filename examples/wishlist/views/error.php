<html xml:lang="no" lang="no">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>Error</title>
		<style type="text/css">
			body {
				margin: 1em;
				padding: 0;
				
				background-color: #fff;
				color: #000;
				font: 90%/1.4 Verdana, Helvetica, Arial, sans-serif;
			}
			
			#error {
				padding: 0.5em;
				border: 0.4em solid red;
				background-color: #ffada7;
			}
		</style>
	</head>
	<body>
		<div id="error">
<?php
/*
 * If logging is disable, we display detailed error.
 */
if (!isset($config['logging']['enabled']) || !$config['logging']['enabled']):
?>
				<h1><?php echo get_class($exception) ?></h1>
				<p id="message">
<?php echo $exception->getMessage() ?>
				</p>
<?php
/*
 * Display SQL query if exception is SQL-related.
 */
if ($exception instanceof SqlException) {
echo <<<HTML
				<h2>Query</h2>
{$exception->getQuery()}
HTML;
} ?>
				<h2>Location</h2>
				<p>
<?php echo $exception->getFile() . ':' . $exception->getLine() ?>
				</p>
				<h2>Stacktrace</h2>
				<pre>
<?php echo $exception->getTraceAsString() ?>
				</pre>
				<h2>Request</h2>
				<pre>
<?php echo print_vars($request) ?>
				</pre>
				<h2>Action Variables</h2>
				<pre>
<?php echo print_vars($action) ?>
				</pre>
<?php else: ?>
				<h1>An error occurred while processing your request</h1>
				<p id="message">
					The error has been logged and we will resolve the problem as soon as possible. Please try
					again in a little while. We apologize for the inconvenience.
				</p>
<?php endif; ?>
		</div>
	</body>
</html>

<?php
/*
 * Convenience function to print variable names and their values.
 */
function print_vars ($vars) {
	foreach ($vars as $var => $value) {
		echo "<strong>$var</strong>: ";
		print_r($value);
		if (!isset($value) || is_scalar($value)) echo "\n";
	}
}
?>
