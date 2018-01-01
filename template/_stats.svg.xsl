<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/2000/svg"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:variable name="scaleX" select="50"/>
	<xsl:variable name="scaleY" select="1 div 2"/>

	<xsl:template match="monthList" mode="svg">
		<xsl:variable name="minY" select="0"/>
		<xsl:variable name="maxY" select="@maxCount"/>
		<xsl:variable name="minX" select="0"/>
		<xsl:variable name="maxX" select="count(month)"/>
		<xsl:variable name="width" select="round(($maxX - $minX) * $scaleX)"/>
		<xsl:variable name="height" select="round(($maxY - $minY) * $scaleY)"/>
		
		
					
		<svg
			contentScriptType="application/javascript"
			contentStyleType="text/css"
			color-rendering="optimizeSpeed"
			shape-rendering="optimizeSpeed"
			text-rendering="optimizeSpeed"
			image-rendering="optimizeSpeed"
			width="{$width}px" height="{$height}px"
			class="log-short"
			>
			<g class="online" transform="translate(0, {$height}) scale(1, -1)">
				<xsl:for-each select="month">
					<xsl:sort select="@time"/>
					<g transform="translate({$scaleX * (position() - 1)}, 0)">
						<xsl:for-each select="user">
							<rect x="0" y="{$scaleY * @offset}" width="{$scaleX}" height="{$scaleY * @count}" data-user="{@name}" title="{@name}: {round(@count)} tweets"/>
						</xsl:for-each>
						<text transform="translate({$scaleX div 2}, 26) rotate(90) scale(1, -1)">
							<xsl:value-of select="@name"/>
							<!--
							<xsl:text>: </xsl:text>
							<xsl:value-of select="@count"/>
							<xsl:text> tweets</xsl:text>
							-->
						</text>
					</g>
				</xsl:for-each>
			</g>
			<g class="labels" transform="translate(0, {$height}) scale(1, -1)">
				<xsl:call-template name="label">
					<xsl:with-param name="i" select="250"/>
					<xsl:with-param name="j" select="$maxY"/>
					<xsl:with-param name="step" select="250"/>
				</xsl:call-template>
			</g>
			
			<g class="axes">
				<path d="M{$minX},{$height} h{$width}"/>
				<path d="M{$minX},{$height} v{-$height}"/>
			</g>
		</svg>
	</xsl:template>
	
	<xsl:template name="label" mode="svg">
		<xsl:param name="i"/>
		<xsl:param name="j"/>
		<xsl:param name="step"/>
		<xsl:if test="$i &lt; $j">
			<rect transform="translate(0, {$scaleY * $i + 1}) scale(1, -1)" width="60" height="2"/>
			<text transform="translate(60, {$scaleY * $i + 2}) scale(1, -1)">
				<xsl:value-of select="$i"/>
			</text>
			<xsl:call-template name="label">
				<xsl:with-param name="i" select="$i + $step"/>
				<xsl:with-param name="j" select="$j"/>
				<xsl:with-param name="step" select="$step"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="*[@data-cms-name='status']/status">
		<g class="status" transform="translate(24, 24)">
			<xsl:for-each select="system">
				<g transform="translate(0, 0)">
					<text y="0em" data-dict="">Last system message:</text>
					<text y="1em" x="1em">
						<tspan class="myTime">[<xsl:value-of select="@date-datetime"/>]</tspan>
						<xsl:text> </xsl:text>
						<xsl:value-of select="@message"/>
					</text>
				</g>
			</xsl:for-each>
			<g transform="translate(384, 0)">
				<text y="0em" data-dict="">Currently online:</text>
				<xsl:choose>
					<xsl:when test="player">
						<xsl:for-each select="player">
							<text y="{position()}em" x="1em">
								<tspan class="myTime">[<xsl:value-of select="@date-datetime"/>]</tspan>
								<xsl:text> </xsl:text>
								<xsl:value-of select="@name"/>
							</text>
						</xsl:for-each>
					</xsl:when>
					<xsl:otherwise>
						<text y="1em" x="1em">-</text>
					</xsl:otherwise>
				</xsl:choose>
			</g>
		</g>
	</xsl:template>
</xsl:stylesheet>