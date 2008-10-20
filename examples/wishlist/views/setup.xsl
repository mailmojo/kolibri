<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:exsl="http://exslt.org/common"
	xmlns:k="http://kolibriproject.com/xml"
	extension-element-prefixes="exsl">

	<!-- Include the file containing the general layout, so a specific view file such as this need only
	     define the page-specific content (the "content" tempalte). -->
	<xsl:include href="layout.xsl" />

	<!-- This variable is injected into the HTML title element in the layout file. -->
	<xsl:variable name="title">Database setup</xsl:variable>

	<xsl:template name="content">
		<h2>Create database tables</h2>
		<p>Although retro, we still need tables in the database.</p>
		<form action="{$webRoot}/setup" method="post">
			<p><input type="submit" name="create" value="OK, create tables" /></p>
		</form>
	</xsl:template>
</xsl:stylesheet>
