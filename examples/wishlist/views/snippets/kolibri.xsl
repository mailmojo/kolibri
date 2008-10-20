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
	
		@param String attribute	The name of the attribute in the model.
		@return NodeSet	The nodeset for the value of the model attribute.
	-->
	<func:function name="k:model-value">
		<xsl:param name="attribute" select="@id" />
		
		<xsl:choose>
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
				<func:result select="$model/*[name() = $attribute]" />
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
		Executes the current form template, retrieving the Kolibri form definition which is subsequently
		parsed into an XHTML form.
		
		@return NodeSet	The XHTML form described by the current form template.
	-->
	<func:function name="k:form">
		<xsl:variable name="structure">
			<xsl:call-template name="form" />
		</xsl:variable>
		
		<func:result>
			<xsl:apply-templates select="exsl:node-set($structure)" />
		</func:result>
	</func:function>
	
	<!-- General attributes on the surrounding element for a complete form field -->
	<xsl:attribute-set name="form-field">
		
		<!-- Add descriptive class names if the form field is required or contains errors -->
		<xsl:attribute name="class">
			<xsl:choose>
				<xsl:when test="@required and k:has-error()">
					<xsl:text>required error</xsl:text>
				</xsl:when>
				<xsl:when test="@required">
					<xsl:text>required</xsl:text>
				</xsl:when>
				<xsl:when test="k:has-error()">
					<xsl:text>error</xsl:text>
				</xsl:when>
			</xsl:choose>
			<xsl:if test="self::k:radio or self::k:checkbox or self::k:hidden">
				<xsl:text> </xsl:text><xsl:value-of select="substring-after(name(), ':')" />
			</xsl:if>
			<xsl:if test="position() mod 2 = 0">
				<xsl:text> even</xsl:text>
			</xsl:if>
		</xsl:attribute>
	</xsl:attribute-set>

	<!--
		Template for parsing <k:form> elements, describing the structure of an XHTML form, optionally
		representing a Kolibri model.
	-->
	<xsl:template match="k:form">
		<!-- Normal form attributes default to the attributes on the k:form element -->
		<xsl:param name="action" select="@action" />
		<xsl:param name="method" select="@method" />
		<xsl:param name="enctype" select="@enctype" />
		
		<form action="{$action}" method="post">
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="$method">
				<xsl:attribute name="method"><xsl:value-of select="$methd" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="$enctype">
				<xsl:attribute name="enctype"><xsl:value-of select="$enctype" /></xsl:attribute>
			</xsl:if>
			
			<!-- Generate form fields for elements we support -->
			<xsl:apply-templates select="k:fieldset|k:div|k:input|k:select|k:radio|k:checkbox|k:textarea|k:hidden|k:submit" />
		</form>
	</xsl:template>
	
	<xsl:template match="k:fieldset">
		<xsl:variable name="customErrors" select="*[local-name() = 'ul'][@class = 'errors']" />
		
		<fieldset>
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="$customErrors">
				<xsl:attribute name="class">error</xsl:attribute>
			</xsl:if>
			<xsl:if test="@legend">
				<legend><xsl:value-of select="@legend" /></legend>
			</xsl:if>
			<xsl:if test="k:legend">
				<legend>
					<label>
						<xsl:apply-templates select="k:legend/*[1]/self::node()" mode="standalone" />
						<xsl:copy-of select="k:legend/text()" />
					</label>
				</legend>
			</xsl:if>
			
			<xsl:apply-templates select="k:div|k:input|k:select|k:radio|k:checkbox|k:textarea|k:hidden" />
			
			<xsl:copy-of select="$customErrors" />
		</fieldset>
	</xsl:template>
	
	<!--
		Matches k:div elements, and k:input or k:textarea which are not contained in a k:div.
	-->
	<xsl:template match="k:div |
			*[name() != 'k:div']/*[self::k:input or self::k:select or self::k:textarea or
				self::k:radio or self::k:checkbox or self::k:hidden]">
		<xsl:variable name="fields" select="k:input|k:select|k:radio|k:checkbox|k:textarea|k:hidden" />
		<xsl:variable name="content">
			<xsl:choose>
				<xsl:when test="self::k:div">
					<!-- Create the first element with ID equal to the label "for" attribute -->
					<xsl:apply-templates select="$fields[position() = 1]" mode="standalone">
						<xsl:with-param name="id" select="@id" />
					</xsl:apply-templates>
					<!-- Create the rest of the field elements, if any -->
					<xsl:apply-templates select="$fields[position() > 1]" mode="standalone" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="." mode="standalone" />
				</xsl:otherwise>
			</xsl:choose>			
		</xsl:variable>

		<div xsl:use-attribute-sets="form-field">
			<xsl:choose>
				<xsl:when test="self::k:div or self::k:input or self::k:select or self::k:textarea">
					<label for="{@id}"><xsl:value-of select="@label" /></label>
					<xsl:copy-of select="$content" />
				</xsl:when>
				<xsl:when test="self::k:hidden">
					<xsl:copy-of select="$content" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="label">
						<xsl:choose>
							<xsl:when test="@label"><xsl:value-of select="@label" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="text()" /></xsl:otherwise>
						</xsl:choose>
					</xsl:variable>

					<label for="{@id}">
						<xsl:copy-of select="$content" />
						<xsl:value-of select="$label" />
					</label>
				</xsl:otherwise>
			</xsl:choose>

			<!-- Show inline info for the form field -->
			<xsl:apply-templates select="k:info" />

			<!-- List all validation errors if there are any -->
			<xsl:choose>
				<xsl:when test="self::k:radio">
					<!--
						Only print out errors if this radio button is the last one of those
						grouped together with it.
					-->
					<xsl:variable name="grouping" select="@name" />
					<xsl:if test=". = //k:radio[@name = $grouping][last()]">
						<xsl:apply-templates select="k:get-errors($grouping)" />
					</xsl:if>
				</xsl:when>
				<xsl:when test="self::k:div">
					<xsl:variable name="id">
						<xsl:choose>
							<xsl:when test="$fields[1]/@id"><xsl:value-of select="$fields[1]/@id" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$fields[1]/@name" /></xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:apply-templates select="k:get-errors($id)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="k:get-errors(@id)" />
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<xsl:template match="k:info">
		<p class="info">
			<xsl:copy-of select="./child::node()" />
		</p>
	</xsl:template>
	
	<xsl:template match="k:input" mode="standalone">
		<xsl:param name="id" select="@id" />
		
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="type">
			<xsl:choose>
				<xsl:when test="@type"><xsl:value-of select="@type" /></xsl:when>
				<xsl:otherwise>text</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="value">
			<xsl:choose>
				<xsl:when test="@value"><xsl:value-of select="@value" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="text()" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="class">
			<xsl:if test="@class"><xsl:value-of select="@class" /></xsl:if>
			<xsl:value-of select="concat(' ', $type)" />
		</xsl:variable>

		<input id="{$id}" name="{$name}" type="{$type}" size="30" value="{$value}" class="{$class}">
			<!-- Allow overriding default values for name, type and size -->
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size" /></xsl:attribute>
			</xsl:if>
			
			<!-- Set optional attributes -->
			<xsl:if test="@disabled = 'disabled'">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:if test="@maxlength">
				<xsl:attribute name="maxlength"><xsl:value-of select="@maxlength" /></xsl:attribute>
			</xsl:if>
			
			<!-- If a current model exists we override the default field value -->
			<xsl:if test="$model and k:model-value($name)">
				<xsl:attribute name="value"><xsl:value-of select="k:model-value($name)" /></xsl:attribute>
			</xsl:if>
		</input>
	</xsl:template>
	
	<xsl:template match="k:select" mode="standalone">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="size">
			<xsl:choose>
				<xsl:when test="@size"><xsl:value-of select="@size" /></xsl:when>
				<xsl:otherwise>1</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<select name="{$name}" size="{$size}">
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:variable name="selected">
				<xsl:value-of select="$model/*[local-name() = $name]" />
			</xsl:variable>
			
			<xsl:choose>
				<xsl:when test="k:option">
					<!-- Explicit k:option elements defined -->
					<xsl:for-each select="k:option">
						<option value="{@value}">
							<xsl:if test="@value = $selected">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="." />
						</option>
					</xsl:for-each>
				</xsl:when>
				<xsl:otherwise>
					<!--
						Use the node set inside k:select as data providers for option elements,
						using the 'value' and 'text' attribute on k:select to get child node values
						for option elements.
					-->
					<xsl:variable name="valueNode" select="@value" />
					<xsl:variable name="textNode" select="@text" />
					
					<xsl:for-each select="*">
						<xsl:variable name="value" select="*[local-name() = $valueNode]" />
						<option value="{$value}">
							<xsl:if test="$value = $selected">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="*[local-name() = $text]" />
						</option>
					</xsl:for-each>
				</xsl:otherwise>
			</xsl:choose>
		</select>
	</xsl:template>
	
	<xsl:template match="k:textarea" mode="standalone">
		<textarea id="{@id}" name="{@id}" cols="30" rows="10">
			<!-- Allow overriding default values for cols and rows -->
			<xsl:if test="@cols">
				<xsl:attribute name="cols"><xsl:value-of select="@cols" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@rows">
				<xsl:attribute name="rows"><xsl:value-of select="@rows" /></xsl:attribute>
			</xsl:if>
			
			<!-- Set optional attributes -->
			<xsl:if test="@disabled = 'disabled'">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:if test="@class">
				<xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute>
			</xsl:if>
			
			<!-- If a current model exists we override the default field value -->
			<xsl:choose>
				<xsl:when test="$model">
					<xsl:value-of select="k:model-value(@id)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@value | ." />
				</xsl:otherwise>
			</xsl:choose>
		</textarea>
	</xsl:template>

	<!--
		Creates a simple checkbox from a k:checkbox element.
	-->
	<xsl:template match="k:checkbox" mode="standalone">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<input type="checkbox" name="{$name}" value="true">
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@value">
				<xsl:attribute name="value"><xsl:value-of select="@value" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="k:model-value(@id) = 'true' or @checked = 'true'">
				<xsl:attribute name="checked">checked</xsl:attribute>
			</xsl:if>
			<xsl:if test="@disabled = 'disabled' or @disabled = 'true'">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
		</input>
	</xsl:template>

	<xsl:template match="k:radio" mode="standalone">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<input type="radio" name="{$name}" value="{@value}">
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@checked and @checked = 'true'">
				<xsl:attribute name="checked">checked</xsl:attribute>
			</xsl:if>
			<xsl:if test="@disabled = 'disabled' or @disabled = 'true'">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
		</input>
	</xsl:template>
	
	<!-- Creates a hidden form field described by a k:hidden field. Exists mostly for completeness right now -->
	<xsl:template match="k:hidden" mode="standalone">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="value">
			<xsl:choose>
				<xsl:when test="@value"><xsl:value-of select="@value" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="k:model-value($name)" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<input type="hidden" name="{$name}" value="{$value}" />
	</xsl:template>
	
	<xsl:template match="k:submit">
		<xsl:variable name="cssClass">
			<xsl:choose>
				<xsl:when test="@class"><xsl:value-of select="@class" /></xsl:when>
				<!-- TODO: Change to something more generic than BEV classes -->
				<xsl:otherwise>knapp kjop</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<div class="submit">
			<!-- If a current model element exists, supply the model ID through a hidden 'original' field -->
			<xsl:if test="$model">
				<input type="hidden" name="original" value="{$model/original}" />
			</xsl:if>
			<!-- Generate any other hidden fields -->
			<xsl:apply-templates select="k:hidden" mode="standalone" />

			<!-- TODO: Remove custom HTML for bev and replace with a general override mechanism -->
			<span class="{$cssClass}">
				<button type="submit" name="{@name}"><xsl:value-of select="@value" /></button>
			</span>
		</div>
	</xsl:template>
</xsl:stylesheet>
