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
			<h1>An error occurred while processing your request</h1>
			<p id="message">
				<?php echo $exception->getMessage() ?>
			</p>
			<h2>Location</h2>
			<p>
				<?php echo $exception->getFile() . ':' . $exception->getLine() ?>
			</p>
			<?php if ($config['debug']): ?>
				<h2>Stacktrace</h2>
				<pre>
<?php echo $exception->getTraceAsString() ?>
				</pre>
				<h2>Request</h2>
				<pre>
<?php print_vars($request->expose()) ?>
				</pre>
				
				<h2>Action Variables</h2>
				<pre>
<?php print_vars($action) ?>
				</pre>
			<?php endif; ?>
		</div>
	</body>
</html>

<?php
function print_vars ($vars) {
	foreach ($vars as $var => $value) {
		echo "<strong>$var</strong>: ";
		print_r($value);
		if (!isset($value)) echo "\n";
	}
}
?>
