<?php
namespace Slothsoft\Farah;

// my_dump(309369146636898304 < 309371004566786048);
// my_dump(strcmp('309369146636898304', '309371004566786048'));die();
$userName = $this->httpRequest->getInputValue('user');
$miscTreshold = 10;
$miscUser = 'misc';
$hashFunction = 'md5';

$file = __FILE__ . '.xml';
if (file_exists($file)) {
    $doc = new \DOMDocument();
    $doc->load($file);
} else {
    $doc = $this->getResourceDoc('/twitter/' . $userName, 'xml');
    $doc->save($file);
}

$timeList = [];
$tweetList = [];
$tweetCount = 0;

$nodeList = $doc->getElementsByTagName('line');
foreach ($nodeList as $node) {
    if ($node->getAttribute('retweeted_status_timestamp')) {
        continue;
    }
    $tweetId = $node->getAttribute('tweet_id');
    $text = $node->getAttribute('text');
    $date = $node->getAttribute('timestamp');
    $time = strtotime($date);
    
    if (preg_match_all('/@\w[\w\d_]+/', $text, $matches, PREG_PATTERN_ORDER)) {
        $userList = $matches[0];
    } else {
        $userList = [];
    }
    foreach ($userList as $i => $user) {
        $user = strtolower($user);
        if ($user === '@' . $userName) {
            unset($userList[$i]);
        }
    }
    if (! $userList) {
        $userList[] = '@' . $userName;
    }
    
    $year = date('Y', $time);
    $month = date('M', $time);
    
    $key = sprintf('%s, %s', $month, $year, $month);
    if (! isset($timeList[$key])) {
        $timeList[$key] = [];
        $timeList[$key]['time'] = $time;
        $timeList[$key]['tweetCount'] = 0;
        $timeList[$key]['userList'] = [];
    }
    $timeList[$key]['tweetCount'] ++;
    $tweetCount ++;
    foreach ($userList as $user) {
        $user = strtolower($user);
        if (! isset($timeList[$key]['userList'][$user])) {
            $timeList[$key]['userList'][$user] = 0;
        }
        $timeList[$key]['userList'][$user] += 1 / count($userList);
        
        if (count($userList) === 1) {
            if (isset($tweetList[$user])) {
                if (strcmp($tweetList[$user], $tweetId) > 0) {
                    $tweetList[$user] = $tweetId;
                }
            } else {
                $tweetList[$user] = $tweetId;
            }
        }
    }
}

$userList = [];

foreach ($timeList as &$time) {
    arsort($time['userList']);
    foreach ($time['userList'] as $user => $count) {
        if (! isset($userList[$user])) {
            $userList[$user] = 0;
        }
        $userList[$user] += $count;
    }
}
unset($time);

arsort($userList);

$miscList = [];
foreach ($userList as $user => &$count) {
    $count = (int) round($count);
    if ($count < $miscTreshold) {
        $miscList[] = $user;
        unset($userList[$user]);
    }
}
unset($count);

$userList[$miscUser] = $tweetCount - array_sum($userList);

foreach ($timeList as &$time) {
    $time['userList'][$miscUser] = 0;
    foreach ($miscList as $user) {
        if (isset($time['userList'][$user])) {
            $time['userList'][$miscUser] += $time['userList'][$user];
            unset($time['userList'][$user]);
        }
    }
}
unset($time);

/*
 * foreach ($timeList as &$time) {
 * printf('%.1f === %.1f%s', $time['tweetCount'], array_sum($time['userList']), PHP_EOL);
 * }
 * unset($time);
 * //
 */

$colorList = [];

$colorList['@faulolio'] = 'rgb(102, 214, 0)';
$colorList['@cal_cla'] = 'rgb(17,139,135)';
$colorList['@elzyzen'] = 'rgb(245, 221, 0)';
$colorList['@damidnighter'] = 'rgb(39, 136, 1)';
$colorList[$miscUser] = 'gray';

foreach ($userList as $user => $count) {
    if (! isset($colorList[$user])) {
        $color = hash($hashFunction, $user);
        $color = '#' . substr($color, 0, 6);
        $colorList[$user] = $color;
    }
}

$retNode = $dataDoc->createDocumentFragment();

foreach ($userList as $user => $count) {
    $arr = [];
    $arr['name'] = $user;
    $arr['count'] = $count;
    $arr['color'] = $colorList[$user];
    if ($user[0] === '@') {
        $arr['href'] = sprintf('https://twitter.com/%s', substr($user, 1));
        if (isset($tweetList[$user])) {
            $arr['href'] = sprintf('https://twitter.com/%s/status/%s', substr($user, 1), $tweetList[$user]);
        }
    }
    
    $userNode = $dataDoc->createElement('user');
    foreach ($arr as $key => $val) {
        $userNode->setAttribute($key, $val);
    }
    $retNode->appendChild($userNode);
}

$monthListNode = $dataDoc->createElement('monthList');
$maxCount = 0;
foreach ($timeList as $name => $time) {
    $arr = [];
    $arr['name'] = $name;
    $arr['count'] = $time['tweetCount'];
    $arr['time'] = $time['time'];
    
    $maxCount = max($maxCount, $arr['count']);
    
    $monthNode = $dataDoc->createElement('month');
    foreach ($arr as $key => $val) {
        $monthNode->setAttribute($key, $val);
    }
    
    $offset = 0;
    foreach ($userList as $user => $tmp) {
        if (isset($time['userList'][$user])) {
            $count = $time['userList'][$user];
            $arr = [];
            $arr['name'] = $user;
            $arr['count'] = $count;
            $arr['offset'] = $offset;
            $offset += $count;
            
            $userNode = $dataDoc->createElement('user');
            foreach ($arr as $key => $val) {
                $userNode->setAttribute($key, $val);
            }
            $monthNode->appendChild($userNode);
        }
    }
    $monthListNode->appendChild($monthNode);
}
$maxCount /= 100;
$maxCount = ceil($maxCount);
$maxCount += 1;
$maxCount *= 100;

$arr = [];
$arr['maxCount'] = $maxCount;
foreach ($arr as $key => $val) {
    $monthListNode->setAttribute($key, $val);
}
$retNode->appendChild($monthListNode);

return $retNode;

//return $doc->documentElement;