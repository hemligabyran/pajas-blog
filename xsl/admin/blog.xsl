<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:myns="http://my.ns.com">

	<xsl:include href="tpl.default.xsl" />

	<myns:js><file>/js/admin.js</file></myns:js>
	<myns:css><file>/css/admin.css</file></myns:css>

	<xsl:template name="tabs">
		<ul class="tabs">
			<xsl:call-template name="tab">
				<xsl:with-param name="href"      select="'blog'" />
				<xsl:with-param name="text"      select="'Blog posts'" />
			</xsl:call-template>

			<xsl:call-template name="tab">
				<xsl:with-param name="href"      select="'blog/blogpost'" />
				<xsl:with-param name="text"      select="'Add blog post'" />
				<xsl:with-param name="action"    select="'blog'" />
				<xsl:with-param name="url_param" select="''" />
			</xsl:call-template>
		</ul>
	</xsl:template>

	<xsl:template match="/">
		<xsl:if test="/root/content[../meta/action = 'index']">
			<xsl:call-template name="template">
				<xsl:with-param name="title"     select="'Admin - Blog posts'" />
				<xsl:with-param name="h1"        select="'Blog posts'" />
				<xsl:with-param name="js_files"  select="document('')/*/myns:js" />
				<xsl:with-param name="css_files" select="document('')/*/myns:css" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/action = 'blogpost' and not(../meta/url_params/id)]">
			<xsl:call-template name="template">
				<xsl:with-param name="title"     select="'Admin - Add post'" />
				<xsl:with-param name="h1"        select="'Add blog post'" />
				<xsl:with-param name="js_files"  select="document('')/*/myns:js" />
				<xsl:with-param name="css_files" select="document('')/*/myns:css" />
				<xsl:with-param name="action"    select="'blogpost'" />
				<xsl:with-param name="url_param" select="''" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/action = 'blogpost' and ../meta/url_params/id]">
			<xsl:call-template name="template">
				<xsl:with-param name="title"     select="'Admin - Edit post'" />
				<xsl:with-param name="h1"        select="'Edit blog post'" />
				<xsl:with-param name="js_files"  select="document('')/*/myns:js" />
				<xsl:with-param name="css_files" select="document('')/*/myns:css" />
				<xsl:with-param name="action"    select="'blogpost'" />
				<xsl:with-param name="url_param" select="'id'" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<!-- List blog posts -->
	<xsl:template match="content[../meta/action = 'index']">
		<table>
			<thead>
				<tr>
					<th class="small_row">ID</th>
					<th>Title</th>
					<th>Date</th>
					<th class="medium_row">Action</th>
				</tr>
			</thead>
			<tbody>
				<xsl:for-each select="blogposts/blogpost">
					<tr>
						<xsl:if test="position() mod 2 = 1">
							<xsl:attribute name="class">odd</xsl:attribute>
						</xsl:if>
						<td><xsl:value-of select="@id" /></td>
						<td style="white-space: nowrap;"><xsl:value-of select="title" /></td>
						<td><xsl:value-of select="published" /></td>
						<td style="white-space: nowrap;">[<a href="blog/blogpost?id={@id}">Edit</a>] [<a href="blog/rm?id={@id}">Delete</a>]</td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>
	</xsl:template>

	<!-- Add or edit blogpost -->
	<xsl:template match="content[../meta/action = 'blogpost']">
		<form method="post">
			<xsl:if test="../meta/url_params/id">
				<xsl:attribute name="action">
					<xsl:text>blog/blogpost?id=</xsl:text>
					<xsl:value-of select="../meta/url_params/id" />
				</xsl:attribute>
			</xsl:if>

			<fieldset>
				<legend>
					<xsl:text>Blog post</xsl:text>
					<xsl:if test="../meta/url_params/id">
						<xsl:text> </xsl:text>
						<xsl:value-of select="../meta/url_params/id" />
					</xsl:if>
				</legend>

				<!-- Title -->
				<xsl:call-template name="form_line">
					<xsl:with-param name="id"    select="'title'" />
					<xsl:with-param name="label" select="'Title:'" />
				</xsl:call-template>

				<!-- Content -->
				<xsl:call-template name="form_line">
					<xsl:with-param name="id"    select="'content'" />
					<xsl:with-param name="label" select="'Content'" />
					<xsl:with-param name="type"  select="'textarea'" />
					<xsl:with-param name="rows"  select="'15'" />
				</xsl:call-template>

				<!-- On first page - ->
				<xsl:call-template name="form_line">
					<xsl:with-param name="id"    select="'on_first_page'" />
					<xsl:with-param name="label" select="'Show on first page'" />
					<xsl:with-param name="type"  select="'checkbox'" />
					<xsl:with-param name="value" select="blog/on_first_page" />
				</xsl:call-template-->

				<!-- Save / Add -->
				<button type="submit" class="longman positive">Save ›</button>

			</fieldset>
		</form>
	</xsl:template>

</xsl:stylesheet>