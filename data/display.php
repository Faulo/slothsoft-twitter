<?php
namespace Slothsoft\CMS;

use Slothsoft\Twitter\Archive;

// require_once '../../constants.php';
$retFragment = null;

if ($userName = $this->httpRequest->getInputValue('user')) {
    if ((int) $this->httpRequest->getInputValue('fetch')) {
        return $this->getDataDoc('/twitter/fetch');
    }
    if ((int) $this->httpRequest->getInputValue('upgrade')) {
        return $this->getDataDoc('/twitter/upgrade');
    }
    
    $options = [];
    $options['limit'] = $this->httpRequest->getInputValue('limit', 10000);
    $options['offset'] = $this->httpRequest->getInputValue('start', - 1);
    $options['pics'] = (int) $this->httpRequest->getInputValue('pics', - 1);
    
    $archive = new Archive($userName);
    if ((int) $this->httpRequest->getInputValue('fetch')) {
        $archive->fetch();
    }
    if ((int) $this->httpRequest->getInputValue('upgrade')) {
        $archive->upgrade();
    }
    $retFragment = $archive->asNode($dataDoc, $options);
}

return $retFragment;