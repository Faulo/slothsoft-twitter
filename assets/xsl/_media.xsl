<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="/data/data">
		<xsl:variable name="tweetList" select=".//tweet"/>
		<html>
			<head>
				<title><xsl:value-of select="user/@name"/>'s Media - Slothsoft's Twitter Thing</title>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
			</head>
			<body>
				<h1><xsl:value-of select="count($tweetList)"/> Tweets</h1>
				<xsl:for-each select=".//year">
					<xsl:variable name="year" select="@no"/>
					<xsl:for-each select="month">
						<xsl:variable name="date" select="concat(@no, '.', $year, ' ')"/>
						<xsl:variable name="month" select="concat(@name, ' 20', $year)"/>
						<section>
							<h3><xsl:value-of select="$month"/></h3>
							<div class="media">
								<xsl:for-each select="$tweetList[contains(@date, $date)]/image">
									<a href="{../@href}" title="{../@text}"><img src="{@href}" alt="{@href}"/></a>
								</xsl:for-each>
							</div>
						</section>
					</xsl:for-each>
				</xsl:for-each>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>