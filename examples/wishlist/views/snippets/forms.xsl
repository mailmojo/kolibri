<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:k="http://kolibriproject.com/xml"
                extension-element-prefixes="exsl func">
		
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
			<xsl:apply-templates select="exsl:node-set($structure)/k:form" />
		</func:result>
	</func:function>
	
	<!--
		Simple function to check if a context node is one of the supported Kolibri form field
		elements.
		
		@return Boolean	true() if the context node is a Kolibri form field.
	-->
	<func:function name="k:is-form-field">
		<func:result select="self::k:input or self::k:radio or self::k:checkbox or self::k:textarea
			or self::k:select or self::k:hidden" />
	</func:function>
	
	<!--
		Generates attributes on the surrounding element of a form field.
		A named template is used to create attributes conditionally.
	-->
	<xsl:template name="form-element-attributes">
		<xsl:variable name="classes">
			<!-- Add descriptive class names if the form field is required or contains errors -->
			<xsl:if test="not(@required) or @required != 'false'">
				<class>required</class>
			</xsl:if>
			<xsl:if test="k:has-error()">
				<class>error</class>
			</xsl:if>
			<!-- Add class name for radio buttons, checkboxes and hidden fields (TODO: All form fields?) -->
			<xsl:if test="self::k:radio or self::k:checkbox or self::k:hidden">
				<class><xsl:value-of select="substring-after(name(), ':')" /></class>
			</xsl:if>
			<xsl:if test="position() mod 2 = 0">
				<class>even</class>
			</xsl:if>
		</xsl:variable>
		
		<!-- Only create the attribute if $classes contains a string with nodes -->
		<xsl:if test="string($classes)">
			<xsl:attribute name="class">
				<xsl:value-of select="k:string-list(exsl:node-set($classes)/*, ' ', ' ')" />
			</xsl:attribute>
		</xsl:if>
	</xsl:template>
	
	<!--
		Generates attributes on a form field, from the more general attributes like id and name to
		the more specific like size or value.
	-->
	<xsl:template name="input-field-attributes">
		<!-- Set ID attribute if specified -->
		<xsl:if test="@id">
			<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
		</xsl:if>

		<!--
			Set name attribute to name attribute or id attribute. Store in variable to use as
			identifier for the value attribute.
		-->
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:attribute name="name"><xsl:value-of select="$name" /></xsl:attribute>
		
		<!-- Set type attribute automatically for radio buttons, check boxes and hidden fields. -->
		<xsl:if test="self::k:radio or self::k:checkbox or self::k:hidden">
			<xsl:attribute name="type"><xsl:value-of select="substring-after(name(), ':')" /></xsl:attribute>
		</xsl:if>
		<xsl:if test="self::k:input">
			<xsl:attribute name="type">
				<xsl:choose>
					<xsl:when test="@type"><xsl:value-of select="@type" /></xsl:when>
					<xsl:otherwise>text</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:attribute name="size">
				<xsl:choose>
					<xsl:when test="@size"><xsl:value-of select="@size" /></xsl:when>
					<xsl:otherwise>30</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:if test="@maxlength">
				<xsl:attribute name="maxlength"><xsl:value-of select="@maxlength" /></xsl:attribute>
			</xsl:if>
		</xsl:if>
		<xsl:if test="self::k:select">
			<xsl:attribute name="size">
				<xsl:choose>
					<xsl:when test="@size"><xsl:value-of select="@size" /></xsl:when>
					<xsl:otherwise>1</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:if test="@multiple">
				<xsl:attribute name="multiple">multiple</xsl:attribute>
			</xsl:if>
		</xsl:if>
		
		<!--
			Set value attribute conditionally, it is required for radio buttons and check boxes.
			For textareas we set the content of the generated element.
		-->
		<xsl:choose>
			<xsl:when test="self::k:checkbox">
				<xsl:variable name="value">
					<xsl:choose>
						<xsl:when test="@value"><xsl:value-of select="@value" /></xsl:when>
						<xsl:otherwise>true</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<!--
					Default value of a check box is simply 'true', which is also
					supported as value of model property to automatically select the check box.
				-->
				<xsl:attribute name="value"><xsl:value-of select="$value" /></xsl:attribute>
				<xsl:if test="k:model-value($name) = $value or @checked = 'true'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
			</xsl:when>
			<xsl:when test="self::k:radio">
				<xsl:attribute name="value"><xsl:value-of select="@value" /></xsl:attribute>
				<xsl:if test="(@value and k:model-value($name) = @value) or @checked = 'true'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
			</xsl:when>
			<xsl:when test="not(self::k:textarea) and not(self::k:select)">
				<!--
					For every normal form field the value is set through 'value' attribute,
					model property value or simply the Kolibri form element's text content.
				-->
				<xsl:choose>
					<xsl:when test="@value">
						<xsl:attribute name="value"><xsl:value-of select="@value" /></xsl:attribute>
					</xsl:when>
					<xsl:when test="$model and string(k:model-value($name))">
						<xsl:attribute name="value"><xsl:value-of select="k:model-value($name)" /></xsl:attribute>
					</xsl:when>
					<xsl:when test="string(text())">
						<xsl:attribute name="value"><xsl:value-of select="text()" /></xsl:attribute>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="self::k:textarea">
				<!-- Textareas has their text content as the value of the form field. -->
				<xsl:choose>
					<xsl:when test="$model and string(k:model-value($name))">
						<xsl:value-of select="k:model-value($name)" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="." />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
		
		<!-- Set disabled status if specified -->
		<xsl:if test="not(self::k:hidden) and (@disabled = 'disabled' or @disabled = 'true')">
			<xsl:attribute name="disabled">disabled</xsl:attribute>
		</xsl:if>
		
		<!-- Set custom class attribute if specified -->
		<xsl:variable name="cssClasses">
			<xsl:if test="@class">
				<class><xsl:value-of select="@class" /></class>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="@type">
					<class><xsl:value-of select="@type" /></class>
				</xsl:when>
				<!-- Default type of an input field is text if @type doesn't exist -->
				<xsl:when test="self::k:input">
					<class>text</class>
				</xsl:when>
				<!-- Radio buttons and check boxes get 'radio' or 'checkbox' as class as well -->
				<xsl:when test="self::k:radio or self::k:checkbox">
					<class><xsl:value-of select="substring-after(name(), ':')" /></class>
				</xsl:when>
			</xsl:choose>
		</xsl:variable>
		<xsl:if test="string($cssClasses)">
			<xsl:attribute name="class">
				<xsl:value-of select="k:string-list(exsl:node-set($cssClasses)/*, ' ', ' ')" />
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<!--
		Generates the content of a form element; either a standalone form field, custom HTML or plain text.
	-->
	<xsl:template name="form-element-content">
		<xsl:variable name="inCustomDiv" select="boolean(self::k:div)" />
		<xsl:for-each select="*|text()">
			<xsl:choose>
				<xsl:when test="k:is-form-field() and $inCustomDiv">
					<xsl:apply-templates select="." mode="standalone" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="." />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>
	
	<!--
		Prints out error messages for a form field. Radio buttons are a special case, where
		errors will only be printed after the last radio button in a button group.
	-->
	<xsl:template name="field-errors">
		<xsl:choose>
			<xsl:when test="self::k:radio">
				<!-- Only print out errors for the last radio button in a group -->
				<xsl:if test=". = //k:radio[@name = current()/@name and last()]">
					<xsl:apply-templates select="k:get-errors(@name)" />
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="id">
					<xsl:choose>
						<xsl:when test="@name or @id">
							<xsl:choose>
								<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
								<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:when test="self::k:div">
							<xsl:choose>
								<xsl:when test="descendant::*/@name[1]">
									<xsl:value-of select="descendant::*/@name[1]" />
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="descendant::*/@id[1]" />
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
					</xsl:choose>
				</xsl:variable>
				<xsl:apply-templates select="k:get-errors($id)" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!--
		Template for parsing <k:form> elements, describing the structure of an XHTML form, optionally
		representing a Kolibri model.
	-->
	<xsl:template match="k:form">
		<form action="{@action}" method="post">
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@method">
				<xsl:attribute name="method"><xsl:value-of select="@method" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@enctype">
				<xsl:attribute name="enctype"><xsl:value-of select="@enctype" /></xsl:attribute>
			</xsl:if>
			
			<!-- Generate form fields with k:form templates -->
			<xsl:apply-templates select="*" />
		</form>
	</xsl:template>
	
	<!--
		Simple fieldset template. Supports adding a legend through using a legend attribute on
		k:fieldset (when no separate legend element exists). Also supports adding error class attribute
		if a typical Kolibri ul element with errors exists within the fieldset.
	-->
	<xsl:template match="k:form/k:fieldset">
		<fieldset>
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="*[name() = 'ul' and @class = 'errors']">
				<xsl:attribute name="class">error</xsl:attribute>
			</xsl:if>
			<xsl:if test="@legend and not(*[name() = 'legend'])">
				<legend><xsl:value-of select="@legend" /></legend>
			</xsl:if>
			
			<!-- Generate form fields with k:form templates -->
			<xsl:apply-templates select="*|text()" />
		</fieldset>
	</xsl:template>
	
	<!--
		Template for k:div elements. Creates a div with a label element. The label will be linked to
		the form field if it's the only field inside the k:div, otherwise a 'for' attribute will need
		to be provided for the k:div element to indicate which field the label should be linked to.
	-->
	<xsl:template match="k:div">
		<xsl:variable name="fields" select="descendant::*[self::k:input or self::k:textarea or
			self::k:select or self::k:radio or self::k:checkbox]" />
		
		<div>
			<xsl:call-template name="form-element-attributes" />
			
			<xsl:choose>
				<xsl:when test="count($fields) > 1 and @label">
					<label>
						<xsl:if test="@for">
							<xsl:attribute name="for"><xsl:value-of select="@for" /></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="@label" />
					</label>
				</xsl:when>
				<xsl:when test="count($fields) = 1">
					<label>
						<xsl:attribute name="for">
							<xsl:choose>
								<xsl:when test="@for"><xsl:value-of select="@for" /></xsl:when>
								<xsl:otherwise><xsl:value-of select="$fields/@id" /></xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
						<xsl:value-of select="$fields/@label" />
					</label>
				</xsl:when>
			</xsl:choose>	

			<!-- Create the actual form element(s) -->
			<xsl:call-template name="form-element-content" />

			<!-- List all validation errors if there are any -->
			<xsl:call-template name="field-errors" />
		</div>
	</xsl:template>
	
	<!--
		Template for all Kolibri form fields not wrapped in a k:div, except hidden fields.
	-->
	<xsl:template match="*[not(parent::k:div) and k:is-form-field() and not(self::k:hidden)]">
		<!-- Fetch label preferably from attribute, otherwise from text content of form field -->
		<xsl:variable name="labelContent">
			<xsl:choose>
				<xsl:when test="@label"><xsl:value-of select="@label" /></xsl:when>
				<xsl:when test="string(text())"><xsl:value-of select="text()" /></xsl:when>
			</xsl:choose>
		</xsl:variable>
		<div>
			<xsl:call-template name="form-element-attributes" />
			
			<!-- Only create label if there's defined content for it -->
			<xsl:if test="$labelContent">
				<label>
					<xsl:if test="@id">
						<xsl:attribute name="for"><xsl:value-of select="@id" /></xsl:attribute>
					</xsl:if>
					<!-- Generate form field inside label for radio buttons and check boxes -->
					<xsl:if test="self::k:radio or self::k:checkbox">
						<xsl:apply-templates select="." mode="standalone" />
					</xsl:if>
					<xsl:value-of select="$labelContent" />
				</label>
			</xsl:if>
			
			<!--
				Generate form field outside label for fields other than radio buttons and check boxes,
				or for radio buttons and check boxes without a label
			-->
			<xsl:if test="not($labelContent) or (not(self::k:radio) and not(self::k:checkbox))">
				<xsl:apply-templates select="." mode="standalone" />
			</xsl:if>
			
			<!-- List all validation errors if there are any -->
			<xsl:call-template name="field-errors" />
		</div>
	</xsl:template>
	
	<!--
		Simple template for creating a hidden form element outside k:div.
	-->
	<xsl:template match="k:hidden">
		<xsl:apply-templates select="." mode="standalone" />
	</xsl:template>
	
	<!--
		Creates a select box from a k:select element with either Kolibri's k:option element
		or a simple custom XML structure as data for the option elements.
		If a custom XML structure is used the 'value' and 'text' attributes need to be supplied
		for the k:select element, containing the name of the XML node or attribute which contains
		each option's value and text.
	-->
	<xsl:template match="k:select" mode="standalone">
		<!-- Find model property identifier and value of the selected option -->
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="@id" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="selected" select="k:model-value($name)" />

		<select>
			<xsl:call-template name="input-field-attributes" />
			
			<xsl:choose>
				<xsl:when test="k:optgroup or k:option">
					<!-- Explicit k:option elements defined -->
					<xsl:apply-templates select="*[self::k:optgroup or self::k:option]">
						<xsl:with-param name="selected" select="$selected" />
					</xsl:apply-templates>
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
						<xsl:variable name="value">
							<xsl:choose>
								<xsl:when test="$valueNode">
									<xsl:value-of select="descendant::*[name() = $valueNode]" />
								</xsl:when>
								<xsl:otherwise><xsl:value-of select="text()" /></xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
						<xsl:variable name="text">
							<xsl:choose>
								<xsl:when test="$textNode">
									<xsl:value-of select="descendant::*[name() = $textNode]" />
								</xsl:when>
								<xsl:otherwise><xsl:value-of select="text()" /></xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
						
						<option value="{$value}">
							<xsl:if test="$value = $selected">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$text" />
						</option>
					</xsl:for-each>
				</xsl:otherwise>
			</xsl:choose>
		</select>
	</xsl:template>
	
	<xsl:template match="k:select/k:optgroup">
		<xsl:param name="selected" />
		<optgroup label="{@label}">
			<xsl:apply-templates select="k:option">
				<xsl:with-param name="selected" select="$selected" />
			</xsl:apply-templates>
		</optgroup>
	</xsl:template>
	
	<xsl:template match="k:option">
		<xsl:param name="selected" />
		<option value="{@value}">
			<xsl:if test="@selected = 'true' or @value = $selected">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="string(.)">
					<xsl:value-of select="." />
				</xsl:when>
				<xsl:otherwise><xsl:value-of select="@value" /></xsl:otherwise>
			</xsl:choose>
		</option>
	</xsl:template>
	
	<!--
		Creates a textarea from k:textarea.
	-->
	<xsl:template match="k:textarea" mode="standalone">
		<textarea cols="30" rows="10">
			<!-- Allow overriding default values for cols and rows -->
			<xsl:if test="@cols">
				<xsl:attribute name="cols"><xsl:value-of select="@cols" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@rows">
				<xsl:attribute name="rows"><xsl:value-of select="@rows" /></xsl:attribute>
			</xsl:if>
			<xsl:call-template name="input-field-attributes" />
		</textarea>
	</xsl:template>

	<!--
		Creates input elements for text/password fields, check boxes, radio buttons and hidden fields from
		k:input, k:checkbox, k:radio and k:hidden fields.
	-->
	<xsl:template match="k:input|k:checkbox|k:radio|k:hidden" mode="standalone">
		<input>
			<xsl:call-template name="input-field-attributes" />
		</input>
	</xsl:template>

	<!--
		Convenience template for creating the submit section of a form. Supplies the
		ID of a model object through a hidden field if a model object exists.
	-->
	<xsl:template match="k:submit">
		<div class="submit">
			<!-- If a current model element exists, supply the model ID through a hidden 'original' field -->
			<xsl:if test="$model and $model/original">
				<input type="hidden" name="original" value="{$model/original}" />
			</xsl:if>
			<!-- Generate any other hidden fields -->
			<xsl:apply-templates select="k:hidden" />

			<span class="submit">
				<button type="submit" name="{@name}">
					<xsl:attribute name="name">
						<xsl:choose>
							<xsl:when test="@name"><xsl:value-of select="@name" /></xsl:when>
							<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
							<xsl:otherwise>save</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:value-of select="@value|@label" />
				</button>
			</span>
		</div>
	</xsl:template>
	
	<!--
		Special templates to allow normal (X)HTML elements to be mixed in with
		k:form and it's related elements.
	-->
	<xsl:template match="*[ancestor::k:form]" priority="-0.5">
		<xsl:element name="{name()}">
			<xsl:copy-of select="@*" />
			<xsl:call-template name="form-element-content" />
		</xsl:element>
	</xsl:template>
</xsl:stylesheet>
