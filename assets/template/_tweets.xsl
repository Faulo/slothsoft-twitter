<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="/data/data">
		<xsl:variable name="tweetList" select=".//tweet"/>
		<html>
			<head>
				<title><xsl:value-of select="user/@name"/> - Slothsoft's Twitter Thing</title>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
			</head>
			<body>
				<h1><xsl:value-of select="count(//tweet)"/> Tweets</h1>
				<xsl:for-each select=".//year">
					<xsl:variable name="year" select="@no"/>
					<xsl:for-each select="month">
						<xsl:variable name="date" select="concat(@no, '.', $year, ' ')"/>
						<xsl:variable name="month" select="concat(@name, ' 20', $year)"/>

						<table>
							<caption><xsl:value-of select="$month"/></caption>
							<tbody>
								<xsl:for-each select="$tweetList[contains(@date, $date)]">
									<tr>
										<td class="time">
											<a href="{@href}"><xsl:value-of select="@date"/></a>
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
											<xsl:for-each select="html">
												<iframe src="{@href}"/>
											</xsl:for-each>
										</td>
									</tr>
								</xsl:for-each>
							</tbody>
						</table>
					</xsl:for-each>
				</xsl:for-each>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>