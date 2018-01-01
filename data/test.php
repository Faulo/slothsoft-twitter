<?php
namespace Slothsoft\CMS;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\Storage;
$dom = new DOMHelper();

$url = 'https://twitter.com/i/tweet/html?id=643752378756370432&modal=gallery';

$data = Storage::loadExternalJSON($url);
$html = $data['tweet_html'];

// data-url
// data-img-src

return $dom->parse($html, null, true);

return HTTPFile::createFromString($html);