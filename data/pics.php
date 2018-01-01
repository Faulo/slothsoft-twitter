<?php
namespace Slothsoft\CMS;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\FileSystem;
use Slothsoft\Core\Image;
$dir1 = dirname(__FILE__) . '/../res/pics/';
$dir2 = dirname(__FILE__) . '/../res/pics_/';

$dir1List = FileSystem::scanDir($dir1);
$dir2List = FileSystem::scanDir($dir2);

$dir1List = array_flip($dir1List);
$dir2List = array_flip($dir2List);

$retNode = $dataDoc->createElementNS(DOMHelper::NS_HTML, 'pre');

foreach ($dir1List as $file => $tmp) {
    if (strpos($file, 'nuigurumisaku') === false) {
        continue;
    }
    if (isset($dir2List[$file])) {
        $arr1 = Image::imageInfo($dir1 . $file);
        $arr2 = Image::imageInfo($dir2 . $file);
        if ($arr1 != $arr2) {
            $retNode->appendChild($dataDoc->createTextNode($file . PHP_EOL));
            $node = $dataDoc->createElementNS(DOMHelper::NS_HTML, 'img');
            $node->setAttribute('src', sprintf('/getResource.php/twitter/pics/%s', $file));
            $node->setAttribute('style', 'display: block');
            $retNode->appendChild($node);
            // break;
        }
    }
}

return $retNode;