<?php
namespace Slothsoft\CMS;

use Slothsoft\Twitter\Archive;

// require_once '../../constants.php';
$retFragment = null;

if ($userName = $this->httpRequest->getInputValue('user')) {
    $options = [];
    $options['limit'] = $this->httpRequest->getInputValue('limit', - 1);
    $options['offset'] = $this->httpRequest->getInputValue('start', - 1);
    $options['pics'] = (int) $this->httpRequest->getInputValue('pics', - 1);
    
    $archive = new Archive($userName);
    
    $tweetList = $archive->getTextList($options);
    
    $frequencyList = [];
    
    foreach ($tweetList as $text) {
        // $text = str_replace('@', '', $text);
        // $text = strtolower($text);
        $arr = preg_split('/[\s,]+/u', $text);
        foreach ($arr as $val) {
            if (strlen($val)) {
                // $val = strtolower($val);
                if (! isset($frequencyList[$val])) {
                    $frequencyList[$val] = 0;
                }
                $frequencyList[$val] ++;
            }
        }
    }
    
    arsort($frequencyList);
    
    $options['limit'] = 1;
    $retFragment = $archive->asNode($dataDoc, $options);
    $wordFragment = $dataDoc->createDocumentFragment();
    
    foreach ($frequencyList as $key => $val) {
        switch (true) {
            case $key[0] === '@':
                $type = 'user';
                break;
            case $key[0] === '#':
                $type = 'hashtag';
                break;
            case preg_match('/^[\w\'…\.]+$/u', $key):
                $type = 'word';
                break;
            default:
                $type = 'symbol';
                break;
        }
        if ($val < 2) {
            break;
        }
        $node = $dataDoc->createElement('word');
        // echo $key . PHP_EOL;
        $node->setAttribute('name', $key);
        $node->setAttribute('count', $val);
        $node->setAttribute('type', $type);
        $wordFragment->appendChild($node);
    }
    
    $retFragment->appendChild($wordFragment);
}

return $retFragment;