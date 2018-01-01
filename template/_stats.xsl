<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:import href="_stats.svg.xsl"/>
	
	<xsl:template match="/data/data">
		<xsl:variable name="userList" select="user"/>
		<html>
			<head>
				<title><xsl:value-of select="user/@name"/> - Twitter Stats</title>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
				<style type="text/css">
<xsl:for-each select="$userList">
	<xsl:value-of select="concat('span[data-user=&quot;', @name, '&quot;] { background-color: ', @color, '; }')"/>
	<xsl:value-of select="concat('rect[data-user=&quot;', @name, '&quot;] { fill: ', @color, '; }')"/>
</xsl:for-each>
				</style>
			</head>
			<body class="stats">
				<div class="diagram">
					<xsl:apply-templates select="monthList" mode="svg"/>
				</div>
				<div class="legend">
					<h2>Number of tweets, by month &amp; recipient</h2>
					<ul>
						<xsl:for-each select="$userList">
							<li>
								<span data-user="{@name}" class="square"/>
								<strong>
									<xsl:choose>
										<xsl:when test="@href">
											<a href="{@href}"><xsl:value-of select="@name"/></a>
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="@name"/>
										</xsl:otherwise>
									</xsl:choose>
								</strong>
								<samp> (<xsl:value-of select="@count"/>)</samp>
							</li>
						</xsl:for-each>
					</ul>
				</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>