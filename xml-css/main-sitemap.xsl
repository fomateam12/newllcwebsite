<?xml version="1.0" encoding="UTF-8"?>
	<xsl:stylesheet version="2.0"
		xmlns:html="http://www.w3.org/TR/REC-html40"
		xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
		xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>XML Sitemap</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<style type="text/css">
				body {
					font-family: Helvetica, Arial, sans-serif;
					font-size: 13px;
					color: #545353;
				}
				table {
					border: none;
					border-collapse: collapse;
				}
				tbody tr td {
					border-bottom: 1px solid #e6e2e2;
				}
				<!-- #sitemap tr:nth-child(odd) td {
					background-color: #eee !important;
				} -->
				#sitemap tbody tr:hover td {
					background-color: #ccc;
				}
				#sitemap tbody tr:hover td, #sitemap tbody tr:hover td a {
					color: #000;
				}
				#content {
					margin: 0 auto;
					width: 1000px;
				}
				.expl {
					margin: 18px 3px;
					line-height: 1.2em;
					font-size: 25px;
				}
				.expl a {
					color: #da3114;
					font-weight: 600;
				}
				.expl a:visited {
					color: #da3114;
				}
				a {
					color: #000;
					text-decoration: none;
				}
				a:visited {
					color: #777;
				}
				a:hover {
					text-decoration: underline;
				}
				td {
					font-size:14px;
					height: 40px;
				}
				th {
					text-align:left;
					padding-right:30px;
					font-size:14px;
					color: #8e8d8d;
				}
				<!-- thead th {
					border-bottom: 1px solid #000;
				} -->
				.btn-primary {
					color: #fff;
					background-color: #376be3;
					border-color: #376be3;
					width: 8%;
				}
				.btn {
					display: block;
					margin-bottom: 0;
					font-weight: normal;
					text-align: center;
					vertical-align: middle;
					touch-action: manipulation;
					cursor: pointer;
					background-image: none;
					border: 1px solid transparent;
					white-space: nowrap;
					padding: 8px 13px;
					font-size: 13px;
					line-height: 1.42857;
					border-radius: 3px;
					-webkit-user-select: none;
					-moz-user-select: none;
					-ms-user-select: none;
					user-select: none;
				}
			</style>
		</head>
		<body>
		<div id="content">
			<div>
				<div style="display:inline-block;">
					<a href="https://webkul.com"><img src="xml-css/logo.png" alt="img"/></a>
				</div>
				<div style="display:inline-block; margin-left: 1%;">
					<div><a href="https://webkul.com" style="color:#464444"><h1>XML Sitemap</h1></a></div>
					<div style="font-size: 16px;">We successfully crawl website and generate XML Sitemap</div>
				</div>
			</div>
			<div>
				<h3>Automatic sitemap creation by webkul <a href="https://webkul.com/opencart-development" style="color: #4d7dea;">Opencart</a> sitemap creator. This is a free <a href="https://store.webkul.com/OpenCart-Modules.html" style="color: #4d7dea;">Opencart extension</a> developed by <a href="https://webkul.com" style="color: #4d7dea;">Webkul</a></h3>
			</div>	
			<hr width="80%" align="left" />
			<div align="right" style="margin-right: 20%;">
				<a href="wk_download_xml.php" class="btn btn-primary" style="color: white;">Download</a>
			</div>	
			<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
				<p class="expl">
					This XML Sitemap Index file contains <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/> sitemaps.
				</p>
				<table id="sitemap" cellpadding="3">
					<thead>
					<tr>
						<th width="75%">Sitemap</th>
						<th width="25%">Last Modified</th>
					</tr>
					</thead>
					<tbody>
					<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
						<xsl:variable name="sitemapURL">
							<xsl:value-of select="sitemap:loc"/>
						</xsl:variable>
						<tr>
							<td>
								<a href="{$sitemapURL}"><xsl:value-of select="sitemap:loc"/></a>
							</td>
							<td>
								<xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)),concat(' ', substring(sitemap:lastmod,20,6)))"/>
							</td>
						</tr>
					</xsl:for-each>
					</tbody>
				</table>
			</xsl:if>
			<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
				<p class="expl">
					This XML sitemap index contains <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> Sitemaps.
				</p>
				<table id="sitemap" cellpadding="3">
					<thead>
					<tr>
						<th width="80%">Sitemap</th>
						<th title="Last Modification Time" width="20%">Last Modified</th>
					</tr>
					</thead>
					<tbody>
					<xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'"/>
					<xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
					<xsl:for-each select="sitemap:urlset/sitemap:url">
						<tr>
							<td>
								<xsl:variable name="itemURL">
									<xsl:value-of select="sitemap:loc"/>
								</xsl:variable>
								<a href="{$itemURL}">
									<xsl:value-of select="sitemap:loc"/>
								</a>
							</td>
							<td>
								<xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)),concat(' ', substring(sitemap:lastmod,20,6)))"/>
							</td>
						</tr>
					</xsl:for-each>
					</tbody>
				</table>
			</xsl:if>
		</div>
		</body>
		</html>
	</xsl:template>
	</xsl:stylesheet>
