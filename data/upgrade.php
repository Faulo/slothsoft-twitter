<?php
namespace Slothsoft\Farah;

use Slothsoft\Core\Lambda\Manager;
use Slothsoft\Twitter\Archive;
$userList = Archive::getUserList();
if ($userName = $this->httpRequest->getInputValue('user')) {
    $userList = [
        $userName
    ];
}

$argsList = [];
foreach ($userList as $user) {
    $args = [];
    $args['user'] = $user;
    $argsList[$user] = $args;
}

$code = '
extract($args);

$archive = new \Twitter\Archive($user);
$ret = $archive->upgrade();
return sprintf("User %s: Upgraded %d tweets!%s", $user, $ret, PHP_EOL);';

return Manager::streamClosureList($code, $argsList);

$responseList = Manager::executeList($code, $argsList);

$retNode = $dataDoc->createDocumentFragment();

foreach ($responseList as $user => $count) {
    $dataNode = $dataDoc->createElement('upgrade');
    $dataNode->setAttribute('user', $user);
    $dataNode->setAttribute('tweets', $count);
    $retNode->appendChild($dataNode);
}

return $retNode;