<?php
namespace Slothsoft\Farah;

// require_once '../../constants.php';

/*
 * $doc = \CMS\HTTPDocument::loadExternalDocument('https://twitter.com/DaMidnighter');
 *
 * $xpath = \CMS\HTTPDocument::loadXPath($doc);
 *
 * $nodeList = $xpath->evaluate('//p[@class="js-tweet-text tweet-text"]');
 *
 * $tweetList = [];
 * foreach ($nodeList as $node) {
 * $tweetList[] = $node->textContent;
 * }
 *
 * $tweetList = array_reverse($tweetList);
 *
 * echo implode(PHP_EOL, $tweetList);
 * //
 */
use Slothsoft\Twitter\Archive;
$dataNode = $dataDoc->createElement('fetch');

if ($userName = $this->httpRequest->getInputValue('user')) {
    $archive = new Archive($userName, true);
    $dataNode->setAttribute('user', $userName);
}
return $dataNode;