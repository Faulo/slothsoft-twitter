<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="/data/data">
		<xsl:variable name="tweetList" select=".//tweet"/>
		<html>
			<head>
				<title><xsl:value-of select="user/@name"/>'s Word Frequency - Slothsoft's Twitter Thing</title>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
			</head>
			<body>
				<h1><xsl:value-of select="user/@name"/>'s Most Commonly Used Words</h1>
				<xsl:call-template name="frequencyTable">
					<xsl:with-param name="type" select="'user'"/>
				</xsl:call-template>
				<xsl:call-template name="frequencyTable">
					<xsl:with-param name="type" select="'hashtag'"/>
				</xsl:call-template>
				<xsl:call-template name="frequencyTable">
					<xsl:with-param name="type" select="'symbol'"/>
				</xsl:call-template>
				<xsl:call-template name="frequencyTable">
					<xsl:with-param name="type" select="'word'"/>
				</xsl:call-template>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template name="frequencyTable">
		<xsl:param name="type"/>
		<xsl:variable name="wordList" select=".//word"/>
		<details>
			<summary><h2>most commonly mentioned <xsl:value-of select="$type"/>s</h2></summary>
			<table>
				<tbody>
					<xsl:for-each select="$wordList[@type = $type]">
						<tr>
							<td class="text"><xsl:value-of select="@name"/></td>
							<td class="text number"><xsl:value-of select="@count"/></td>
						</tr>
					</xsl:for-each>
				</tbody>
			</table>
		</details>
	</xsl:template>
</xsl:stylesheet>