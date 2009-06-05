<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:k="http://kolibriproject.com/xml">

	<!-- Include the file containing the general layout, so a specific view file such as this need only
	     define the page-specific content (the "content" template). -->
	<xsl:include href="layout.xsl" />

	<!-- This variable is injected into the HTML title element in the layout file. -->
	<xsl:variable name="title">Front page</xsl:variable>

	<xsl:template name="content">
		<div id="list">
			<div id="want">
				<h2>I want this:</h2>
				<xsl:variable name="want" select="items/Item[not(received)]" />
				<xsl:if test="count($want) = 0">
					<p>I don't want nothing! (Life's perfect.)</p>
				</xsl:if>
				<xsl:apply-templates select="$want" mode="want" />
			</div>

			<div id="have">
				<h2>I have this:</h2>
				<xsl:variable name="have" select="items/Item[received]" />
				<xsl:if test="count($have) = 0">
					<p>My life is empty. :-(</p>
				</xsl:if>
				<xsl:apply-templates select="$have" mode="have" />
			</div>
		</div>

		<div id="form">
			<h2>Wish for a thing</h2>
			<!-- The k:form() function generates a HTML form from a template named "form" (see below). -->
			<xsl:copy-of select="k:form()" />
		</div>
	</xsl:template>

	<!-- Template for wanting things. -->
	<xsl:template match="Item" mode="want">
		<div class="item">
			<h3><xsl:value-of select="name" /></h3>
			<p><xsl:value-of select="description" /></p>
			<xsl:if test="price">
				<span class="price">Price: <xsl:value-of select="price" /></span>
			</xsl:if>
			<p class="actions">
				<a href="{$webRoot}/items/{name}/have">Got it!</a>
				/
				<a href="{$webRoot}/items/{name}/del">Nah, don't want anymore</a>
			</p>
		</div>
	</xsl:template>

	<!-- Template for having things. -->
	<xsl:template match="Item" mode="have">
		<div class="item">
			<h3><xsl:value-of select="name" /></h3>
			<p><xsl:value-of select="description" /></p>
			<p class="actions">
				<a href="{$webRoot}/items/{name}/del">I lost it :-(</a>
			</p>
		</div>
	</xsl:template>

	<!-- Template for the form to add wanting things. Kolibri forms takes care of printing errors, populating
	     fields with values, and other cute things normally very verbose when done manually. -->
	<xsl:template name="form">
		<k:form action="{$webRoot}/items/add">
			<k:input id="name" label="The thing" size="30" required="true" />
			<k:textarea id="description" label="Description" cols="30" rows="3" />
			<k:input id="price" label="Price" size="5" />
			<k:submit name="save" value="Me want" />
		</k:form>
	</xsl:template>
</xsl:stylesheet>
