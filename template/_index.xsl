<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="/data">
		<html>
			<head>
				<title>Slothsoft's Twitter Thing</title>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
			</head>
			<body>
				<h1><xsl:value-of select="count(//tweet)"/> Tweets</h1>
				<table>
					<xsl:for-each select="//tweet">
						<xsl:sort select="@time" order="descending" data-type="number"/>
						<tr>
							<td class="time">
								<a href="{@href}"><xsl:value-of select="@date"/></a>
								<xsl:value-of select="../@name"/>
							</td>
							<td class="text">
								<p><xsl:value-of select="translate(@text, '&#13;', '&#10;')"/><xsl:text>
</xsl:text></p>
							</td>
							<td>
								<xsl:for-each select="image">
									<a href="{@href}"><img src="{@href}" alt="{@href}"/></a>
								</xsl:for-each>
							</td>
							<td>
								<xsl:for-each select="video">
									<video src="{@href}" controls="controls"/>
								</xsl:for-each>
							</td>
						</tr>
					</xsl:for-each>
				</table>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>