<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml">
	<xsl:output method="xml" encoding="utf-8" indent="yes"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

	<xsl:template match="/error">
		<html xml:lang="no" lang="no">
			<head>
				<meta http-equiv="content-type" content="text/html; charset=utf-8" />
				<title>Error #<xsl:value-of select="id" /></title>
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
						<xsl:value-of select="message" />
					</p>
					<h2>Location</h2>
					<p>
						<xsl:value-of select="location" />
					</p>
					<xsl:if test="boolean(stack)">
						<h2>Stacktrace</h2>
						<pre>
							<xsl:value-of select="stack" />
						</pre>
					</xsl:if>
				</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
