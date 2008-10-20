<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml">

	<xsl:template match="msg">
		<div id="message">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="success = 'true'">
						<xsl:value-of select="'success'" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="'error'" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<div class="content">
				<p><xsl:value-of disable-output-escaping="yes" select="message" /></p>
				<xsl:apply-templates select="details" />
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="details">
		<xsl:if test="count(*) > 0">
			<ul>
				<xsl:for-each select="*">
					<li><xsl:value-of disable-output-escaping="yes" select="." /></li>
				</xsl:for-each>
			</ul>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
