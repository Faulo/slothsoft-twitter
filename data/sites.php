<?php
namespace Slothsoft\CMS;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Twitter\Archive;
$baseXML = '
<page name="%1$s">
	<param name="user" value="%1$s"/>
	<page name="Tweets" ref="tweets">
		<param name="limit" value="256"/>
	</page>
	<page name="Media" ref="media">
		<param name="limit" value="-1"/>
	</page>
	<page name="frequency" ref="frequency">
		<param name="limit" value="-1"/>
	</page>
</page>
';

$retXML = '';

$userList = Archive::getUserList();
foreach ($userList as $user) {
    $retXML .= sprintf($baseXML, $user);
}

$dom = new DOMHelper();
return $dom->parse($retXML, $dataDoc);