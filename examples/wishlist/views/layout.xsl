<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:exsl="http://exslt.org/common"
	xmlns:k="http://kolibriproject.com/xml"
	extension-element-prefixes="exsl"
	exclude-result-prefixes="k">

	<xsl:output method="xml" encoding="utf-8" indent="yes"
		omit-xml-declaration="yes"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

	<!-- We receive values config setting with the corresponding keys. -->
	<xsl:param name="staticRoot" />
	<xsl:param name="debug" />

	<!-- Include Kolibri snippets, helpers for forms and status messages. -->
	<xsl:include href="snippets/kolibri.xsl" />
	<xsl:include href="snippets/message.xsl" />

	<xsl:template match="/result">
		<html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			<meta http-equiv="imagetoolbar" content="no" />
			<title><xsl:value-of select="$title" /> ~ Whishlist Kolibri Demo App</title>

			<!-- Simply an convenience for including CSS files in a "css" directory in "staticRoot". -->
			<xsl:copy-of select="k:css('style')" />
		</head>
		<body>
			<h1>My [retro] whishlist</h1>

			<!-- Displays status message if present. -->
			<xsl:if test="msg">
				<div id="status">
						<xsl:apply-templates select="msg" />
				</div>
			</xsl:if>

			<div id="content">
				<!-- Actual contents from specific view files comes here. -->
				<xsl:call-template name="content" />
			</div>

			<div id="footer">
				<p>
					At least I don't have to wish for a footer
				</p>
			</div>

			<!-- When debug is enabled we output the actual XML data. Useful to check if expected data is present. -->
			<xsl:if test="$debug = 1">
				<div class="debug">
					<xsl:copy-of select="*" />
				</div>
			</xsl:if>
		</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
