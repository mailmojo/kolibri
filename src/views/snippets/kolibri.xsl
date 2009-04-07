<?xml version="1.0" encoding="utf-8"?>
<!--
	Templates for custom Kolibri elements (like k:form), and an experimental collection of eXSLT functions.
	
	@version	$Id$
-->
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:k="http://kolibriproject.com/xml"
                extension-element-prefixes="exsl func">

	<xsl:include href="message.xsl" />
	<xsl:include href="forms.xsl" />
	
	<!-- Makes the XML structures for model and errors available for the custom element templates -->
	<xsl:variable name="model" select="/result/model" />
	<xsl:variable name="errors" select="/result/errors" />
		
	<func:function name="k:number">
		<xsl:param name="value" />
		
		<func:result>
			<xsl:choose>
				<xsl:when test="number($value)">
					<xsl:value-of select="number($value)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="0" />
				</xsl:otherwise>
			</xsl:choose>
		</func:result>
	</func:function>
	
	<!--
		Function for merging a node set with string values to a string separated by a common string.
		Useful for creating comma separated lists of string values. Also supports a different separator
		infront of the last value - useful for sentences like 'apples, oranges and bananas'.
	
		@param NodeSet nodes	The node set with string values to merge.
		@param String lastSep	The separator infront of the last value, defaults to plain ', '.
		@param String sep		The main separator string, defaults to plain ', '.
		@return String	String with merged values.
	-->
	<func:function name="k:string-list">
		<xsl:param name="nodes" />
		<xsl:param name="lastSep" select="', '" />
		<xsl:param name="sep" select="', '" />
		
		<func:result>
			<xsl:for-each select="$nodes">
				<xsl:choose>
					<xsl:when test="position() &gt; 1 and position() &lt; last()">
						<xsl:value-of select="$sep" />
					</xsl:when>
					<xsl:when test="position() != 1 and position() = last()">
						<xsl:value-of select="$lastSep" />
					</xsl:when>
				</xsl:choose>
				<xsl:value-of select="." />
			</xsl:for-each>
		</func:result>
	</func:function>
	
	<func:function name="k:truncate">
		<xsl:param name="str" />
		<xsl:param name="maxlen" />
		
		<xsl:choose>
			<xsl:when test="($maxlen + 1) > string-length($str)">
				<func:result select="$str" />
			</xsl:when>
			<xsl:otherwise>
				<func:result>
					<xsl:value-of select="substring($str, 1, $maxlen - 1)" />…
				</func:result>
			</xsl:otherwise>
		</xsl:choose>
	</func:function>
	<!--
		Convenience function for linking to an external CSS file from the configured root of static files.
		
		@param String file	The name of the file, without the file extension (always .css).
		@return NodeSet	The link-element which includes the CSS file into the (X)HTML document.
	-->
	<func:function name="k:css">
		<xsl:param name="file" />
		<xsl:param name="media" select="'screen'" />
		
		<func:result>
			<link rel="stylesheet" href="{$staticRoot}/css/{$file}.css" type="text/css" media="{$media}" charset="utf-8" />
		</func:result>
	</func:function>
	
	<!--
		Fetches the value of a model attribute.
		
		TODO: Generalize to k:object-value, where object by default is $model. This way,
		:: attributes could be returned recursively (since objects/arrays are the same in XML).
		And the function could be used for more than models.
		
		@param String attribute	The name of the attribute in the model.
		@return NodeSet	The nodeset for the value of the model attribute.
	-->
	<func:function name="k:model-value">
		<xsl:param name="attribute" select="@id" />
		
		<xsl:choose>
			<xsl:when test="contains($attribute, '[') and not(contains($attribute, '[]'))">
				<xsl:variable name="prop" select="substring-before($attribute, '[')" />
				<xsl:variable name="child" select="substring-before(substring-after($attribute, '['), ']')" />
				
				<func:result select="$model/*[name() = $prop]/*[name() = $child]" />
			</xsl:when>
			<xsl:when test="contains($attribute, '::')">
				<xsl:variable name="prop" select="substring-before($attribute, '::')" />
				<xsl:variable name="child">
					<xsl:choose>
						<xsl:when test="contains($attribute, '[]')">
							<xsl:value-of select="substring-before(substring-after($attribute, '::'), '[]')" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring-after($attribute, '::')" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="pos">
					<xsl:choose>
						<xsl:when test="contains($attribute, '[]')">
							<!-- Find the unique identifier of the current field in the list -->
							<xsl:variable name="current" select="generate-id(.)" />
							<!-- Calculate the position of the current field among all fields in the list -->
							<!-- TODO: Try to find a count() expression that does the same -->
							<xsl:for-each select="//*[@name = $attribute]">
								<xsl:if test="generate-id() = $current">
									<xsl:value-of select="position()" />
								</xsl:if>
							</xsl:for-each>
						</xsl:when>
						<xsl:otherwise>1</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<func:result select="$model/*[name() = $prop]/*[position() = $pos]/*[name() = $child]" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="pureName">
					<xsl:choose>
						<xsl:when test="contains($attribute, '[')">
							<xsl:value-of select="substring-before($attribute, '[')" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$attribute" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<func:result select="$model/*[name() = $pureName]" />
			</xsl:otherwise>
		</xsl:choose>
	</func:function>
	
	<!--
		Fetches all errors for a model attribute, if any.
		
		@param String attribute	The name of the model attribute to fetch errors for.
		@return NodeSet	The nodeset with all errors reported for the model attribute.
	-->
	<func:function name="k:get-errors">
		<xsl:param name="attribute" select="@id" />
		<!-- Hmm, why doesn't it work to select $errorTxt in the result? -->
		<!--xsl:variable name="errorTxt">
			<xsl:value-of disable-output-escaping="yes" select="$errors/*[name() = $attribute]" />
		</xsl:variable-->

		<func:result select="$errors/*[name() = $attribute]" />
	</func:function>
	
	<!--
		Convenience function to check if a model attribute has any reported errors.
		
		@param String attribute	The name of the model attribute.
		@return Boolean	<code>true()</code> if errors for the model attribute exists,
						or <code>false()</code> otherwise.
	-->
	<func:function name="k:has-error">
		<xsl:param name="attribute">
			<xsl:choose>
				<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:when test="*/@id"><xsl:value-of select="*/@id[1]" /></xsl:when>
				<xsl:when test="*/@name"><xsl:value-of select="*/@name[1]" /></xsl:when>
			</xsl:choose>
		</xsl:param>
			
		<func:result select="boolean($errors/*[name() = $attribute])" />
	</func:function>
	
	<!--
		Prints out a list of errors for an element.
	-->
	<xsl:template match="errors/*">
		<ul class="errors">
			<xsl:for-each select="*">
				<li><xsl:value-of select="." /></li>
			</xsl:for-each>
		</ul>
	</xsl:template>
</xsl:stylesheet>
