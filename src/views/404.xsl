<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml">
	<xsl:output method="xml" encoding="utf-8" indent="yes"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

	<xsl:template match="/">
		<html xml:lang="no" lang="no">
			<head>
				<meta http-equiv="content-type" content="text/html; charset=utf-8" />
				<title>404 Not Found</title>
				<style type="text/css">
					body {
						margin: 1em;
						padding: 0;
					
						background-color: #fff;
						color: #000;
						font: 90%/1.4 Verdana, Helvetica, Arial, sans-serif;
					}
					
					hr {
						border: 0;
						border-top: 1px solid black;
					}
					
					#systeminfo {
						font-size: 70%;
						font-style: italic;
						text-align: right;
						color: #666;
					}
				</style>
			</head>
			<body>
				<h1>404 Not Found</h1>
				<p>
					Sorry, but the resource you requested does not exist.
				</p>
				<hr />
				<p id="systeminfo">kolibri</p>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
