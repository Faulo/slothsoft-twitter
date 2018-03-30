<?php
namespace Slothsoft\Farah;

use Slothsoft\Twitter\Archive;
$retFragment = $dataDoc->createDocumentFragment();

$options = [];
$options['limit'] = $this->httpRequest->getInputValue('limit', 256);
$options['offset'] = $this->httpRequest->getInputValue('start', - 1);
$options['pics'] = (int) $this->httpRequest->getInputValue('pics', - 1);

$userList = Archive::getUserList();
foreach ($userList as $user) {
    $archive = new Archive($user);
    // $archive->upgrade();
    $retFragment->appendChild($archive->asNode($dataDoc, $options));
}

return $retFragment;