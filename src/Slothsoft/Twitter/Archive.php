<?php
namespace Slothsoft\Twitter;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\Storage;
use Slothsoft\Core\IO\HTTPFile;
use Slothsoft\DBMS\Manager as DBMS;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;

class Archive
{

    const VERSION = 31;

    const HTTP_CACHETIME = - 1;

    const DIR_IMAGES = 'mod/twitter/res/pics/';

    const URL_IMAGES = '/getResource.php/twitter/pics/';

    const DIR_HTML = 'mod/twitter/res/html/';

    const URL_HTML = '/getResource.php/twitter/html/';

    const DB_NAME = 'twitter';

    const TWITTER_URL_TWEET = 'https://twitter.com/%s/status/%s';

    // const TWITTER_URL_FETCH = 'https://twitter.com/i/profiles/show/%s/timeline/with_replies?include_available_features=0&include_entities=0';
    const TWITTER_URL_FETCH = 'https://twitter.com/%s';

    const TWITTER_URL_FETCH_ALL = 'https://twitter.com/%s/with_replies';

    const TWITTER_URL_FETCH_MEDIA = 'https://twitter.com/%s/media';

    const TWITTER_URL_RSS = 'https://queryfeed.net/tw?q=%%40%s';

    const TWITTER_URL_MEDIA = 'https://twitter.com/i/tweet/html?id=%s&modal=gallery';

    const TWITTER_URL_HTML = 'https://twitter.com%s';

    // const TWITTER_URL_FETCH = 'https://twitter.com/i/profiles/show/ElzyZen/timeline/with_replies?include_available_features=1&include_entities=1&last_note_ts=1437428962&max_position=621850787824697345';
    public static function getUserList()
    {
        $db = DBMS::getDatabase(self::DB_NAME);
        return $db->getTableList();
    }

    protected $dbName;

    protected $tableName;

    protected $userName;

    protected $dbmsTable;

    protected $dom;

    protected $doc;

    protected $xpath;

    public function __construct($userName, $autocreate = false)
    {
        $this->dbName = self::DB_NAME;
        $this->userName = $userName;
        $this->tableName = strtolower($this->userName);
        
        $this->dbmsTable = DBMS::getTable($this->dbName, $this->tableName);
        
        if ($autocreate and ! $this->dbmsTable->tableExists()) {
            $this->install();
        }
        
        $this->dom = new DOMHelper();
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->xpath = new DOMXPath($this->doc);
    }

    protected function install()
    {
        $sqlCols = [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT',
            'version' => 'tinyint unsigned NOT NULL DEFAULT "0"',
            'tweet_time' => 'int unsigned NOT NULL',
            'tweet_id' => 'bigint unsigned NOT NULL',
            'tweet_user' => 'bigint unsigned NOT NULL',
            'tweet_text' => 'tinytext NOT NULL',
            'tweet_media' => 'text NOT NULL',
            'tweet_html' => 'text NOT NULL'
        ];
        $sqlKeys = [
            'id',
            'version',
            'tweet_time',
            [
                'type' => 'UNIQUE KEY',
                'columns' => [
                    'tweet_id'
                ]
            ]
        ];
        $this->dbmsTable->createTable($sqlCols, $sqlKeys);
    }

    public function getTextList(array $options)
    {
        $limit = $options['limit'] > 0 ? sprintf('1 LIMIT %d', $options['limit']) : '1';
        return $this->dbmsTable->select('tweet_text', $limit);
    }

    public function asNode(DOMDocument $dataDoc, array $options)
    {
        $retNode = $dataDoc->createElement('user');
        $retNode->setAttribute('name', $this->userName);
        
        $limit = $options['limit'] > 0 ? sprintf('LIMIT %d', $options['limit']) : '';
        $offset = $options['offset'] > 0 ? sprintf('OFFSET %d', $options['offset']) : '';
        $dataList = $this->dbmsTable->select([
            'tweet_id',
            'tweet_text',
            'tweet_time',
            'tweet_media'
        ], sprintf('1 ORDER BY tweet_time DESC %s %s', $limit, $offset));
        $dateList = [];
        foreach ($dataList as $data) {
            $append = true;
            $hasMedia = false;
            $node = $dataDoc->createElement('tweet');
            $node->setAttribute('id', $data['tweet_id']);
            $node->setAttribute('text', $data['tweet_text']);
            $node->setAttribute('time', $data['tweet_time']);
            $node->setAttribute('date', date(DateTimeFormatter::FORMAT_DATETIME, $data['tweet_time']));
            $node->setAttribute('href', sprintf(self::TWITTER_URL_TWEET, $this->userName, $data['tweet_id']));
            if ($data['tweet_media']) {
                $media = json_decode($data['tweet_media'], true);
                foreach ($media as $key => $list) {
                    foreach ($list as $val) {
                        $mediaNode = $dataDoc->createElement($key);
                        $mediaNode->setAttribute('href', $val);
                        $node->appendChild($mediaNode);
                        $hasMedia = true;
                    }
                }
            }
            switch ($options['pics']) {
                case - 1:
                    break;
                case 0:
                    while ($node->hasChildNodes()) {
                        $node->removeChild($node->lastChild);
                    }
                    break;
                case 1:
                    $append = $hasMedia;
                    break;
            }
            if ($append) {
                $year = date('y', $data['tweet_time']);
                $month = date('m', $data['tweet_time']);
                if (! isset($dateList[$year])) {
                    $dateList[$year] = [];
                }
                if (! isset($dateList[$year][$month])) {
                    $dateList[$year][$month] = date('F', $data['tweet_time']);
                }
                $retNode->appendChild($node);
            }
        }
        foreach ($dateList as $year => $monthList) {
            $yearNode = $dataDoc->createElement('year');
            $yearNode->setAttribute('no', $year);
            foreach ($monthList as $month => $name) {
                $monthNode = $dataDoc->createElement('month');
                $monthNode->setAttribute('no', $month);
                $monthNode->setAttribute('name', $name);
                $yearNode->appendChild($monthNode);
            }
            $retNode->appendChild($yearNode);
        }
        return $retNode;
    }

    public function upgrade()
    {
        $ret = 0;
        $max = 100;
        $limit = 1000;
        $lastId = 0;
        do {
            if ($tweetList = $this->dbmsTable->select([
                'id',
                'tweet_id',
                'tweet_html'
            ], sprintf('id > %d AND version < %d', $lastId, self::VERSION), sprintf('ORDER BY id LIMIT %d', $max))) {
                foreach ($tweetList as $tweet) {
                    $lastId = $tweet['id'];
                    if ($this->fetchTweet($tweet['tweet_id'], $tweet['tweet_html'], false, $tweet['id'])) {
                        $ret ++;
                    }
                }
            }
        } while ($tweetList and $ret < $limit);
        return $ret;
    }

    public function fetch($fetchAll = false)
    {
        $ret = 0;
        
        $maxId = null;
        $baseURI = sprintf(self::TWITTER_URL_FETCH_MEDIA, $this->userName);
        $useCache = false;
        do {
            $hasMoreItems = false;
            $success = $fetchAll;
            $uri = $baseURI;
            if ($maxId) {
                $uri .= sprintf('?max_position=%s', $maxId);
                $maxId = null;
                $useCache = true;
            }
            
            // echo $uri . PHP_EOL;
            if ($xpath = $this->downloadXPath($uri, $useCache)) {
                // output($xpath->document);die;
                $hasMoreItems = true;
                $nodeList = $xpath->evaluate('//*[@data-item-id]');
                foreach ($nodeList as $node) {
                    $tweetId = $node->getAttribute('data-item-id');
                    $tweetHTML = $this->dom->stringify($node);
                    $maxId = $tweetId;
                    if ($this->fetchTweet($tweetId, $tweetHTML, true)) {
                        $success = true;
                        $ret ++;
                    }
                }
            } else {
                trigger_error("Unable to downloadXPath $uri", E_USER_NOTICE);
            }
        } while ($maxId and $hasMoreItems and $success);
        
        $maxId = null;
        $baseURI = sprintf(self::TWITTER_URL_FETCH, $this->userName);
        $useCache = false;
        do {
            $hasMoreItems = false;
            $success = $fetchAll;
            $uri = $baseURI;
            if ($maxId) {
                $uri .= sprintf('?max_position=%s', $maxId);
                $maxId = null;
                $useCache = true;
            }
            
            // echo $uri . PHP_EOL;
            if ($xpath = $this->downloadXPath($uri, $useCache)) {
                // output($xpath->document);die;
                $hasMoreItems = true;
                $nodeList = $xpath->evaluate('//*[@data-item-id]');
                foreach ($nodeList as $node) {
                    $tweetId = $node->getAttribute('data-item-id');
                    $tweetHTML = $this->dom->stringify($node);
                    $maxId = $tweetId;
                    if ($this->fetchTweet($tweetId, $tweetHTML, true)) {
                        $success = true;
                        $ret ++;
                    }
                }
            } else {
                trigger_error("Unable to downloadXPath $uri", E_USER_NOTICE);
            }
        } while ($maxId and $hasMoreItems and $success);
        
        $uri = sprintf(self::TWITTER_URL_RSS, $this->userName);
        $useCache = false;
        
        // echo $uri . PHP_EOL;
        if ($xpath = $this->downloadXPath($uri, $useCache)) {
            // output($xpath->document);die;
            $nodeList = $xpath->evaluate('//item');
            foreach ($nodeList as $node) {
                $tweetURL = $xpath->evaluate('normalize-space(guid)', $node);
                if (preg_match('~/(\d+)$~', $tweetURL, $match)) {
                    // echo $tweetURL . PHP_EOL;
                    $tweetId = $match[1];
                    $tweetHTML = null;
                    if ($this->fetchTweet($tweetId, $tweetHTML, true)) {
                        // echo $tweetId . PHP_EOL;
                        $ret ++;
                    }
                }
            }
        } else {
            trigger_error("Unable to downloadXPath $uri", E_USER_NOTICE);
        }
        
        return $ret;
    }

    public function fetchTweet($tweetId, $tweetHTML, $isHTML, $id = null)
    {
        $ret = false;
        
        if ($data = $this->downloadTweet($tweetId)) {
            $tweetHTML = $data['tweet_html'];
            $isHTML = true;
        }
        
        if ($tweetHTML) {
            if ($tweet = $this->parseTweet($tweetId, $tweetHTML, $isHTML)) {
                try {
                    $ret = $id === null ? $this->dbmsTable->insert($tweet, $tweet) : $this->dbmsTable->update($tweet, $id);
                } catch (Exception $e) {
                    throw new Exception($id === null ? 'invalid insert?' . PHP_EOL . $tweetId . PHP_EOL . print_r($tweet, true) : 'invalid update?' . PHP_EOL . $id . PHP_EOL . print_r($tweet, true));
                }
            } else {
                throw new Exception('invalid parsing?' . PHP_EOL . $tweetId . PHP_EOL . $tweetHTML);
            }
        }
        return $ret;
    }

    protected function parseTweet($tweetId, $tweetHTML, $isHTML)
    {
        $ret = null;
        if ($tweetNode = $this->dom->parse($tweetHTML, $this->doc, $isHTML)) {
            if ($textNode = $this->xpath->evaluate('.//*[@lang] | .//p', $tweetNode)->item(0)) {
                $tweet = [];
                $tweet['version'] = self::VERSION;
                $tweet['tweet_id'] = $tweetId;
                $tweet['tweet_html'] = $isHTML ? $this->dom->stringify($tweetNode) : $tweetHTML;
                $tweet['tweet_time'] = (int) $this->xpath->evaluate('number(.//@data-time)', $tweetNode);
                $tweet['tweet_user'] = $this->xpath->evaluate('string(.//@data-user-id)', $tweetNode);
                // $tweet['tweet_recipient'] = $this->xpath->evaluate('string(.//@data-reply-to-users-json)', $tweetNode);
                
                $tmpList = $this->xpath->evaluate('.//img[@alt]', $textNode);
                $nodeList = [];
                foreach ($tmpList as $tmp) {
                    $nodeList[] = $tmp;
                }
                foreach ($nodeList as $node) {
                    $node->parentNode->replaceChild($node->ownerDocument->createTextNode($node->getAttribute('alt')), $node);
                }
                $tweet['tweet_text'] = $this->xpath->evaluate('string(.)', $textNode);
                
                if ($recipientList = $this->xpath->evaluate('string(.//@data-reply-to-users-json)', $tweetNode)) {
                    $recipientList = json_decode($recipientList, true);
                    foreach ($recipientList as $recipient) {
                        if ($recipient['id_str'] !== $tweet['tweet_user']) {
                            $name = '@' . $recipient['screen_name'];
                            if (strpos($tweet['tweet_text'], $name) === false) {
                                $tweet['tweet_text'] = $name . ' ' . $tweet['tweet_text'];
                            }
                        }
                    }
                }
                
                $media = [];
                
                $media['image'] = [];
                $nodeList = $this->xpath->evaluate('descendant-or-self::*/@data-image-url', $tweetNode);
                foreach ($nodeList as $node) {
                    if ($href = $this->xpath->evaluate('string(.)', $node)) {
                        if ($href = $this->downloadImage($href)) {
                            $media['image'][] = $href;
                        }
                    }
                }
                $footerHTML = $tweetHTML . $this->xpath->evaluate('string(descendant-or-self::*/@data-expanded-footer)', $tweetNode);
                $footerHTML = str_replace('<img src', '-url-large', $footerHTML);
                if (preg_match_all('/-url-large="(http.+?)"/', $footerHTML, $matchList)) {
                    $hrefList = array_unique($matchList[1]);
                    foreach ($hrefList as $href) {
                        if ($href = $this->downloadImage($href)) {
                            $media['image'][] = $href;
                        }
                    }
                }
                $media['image'] = array_unique($media['image']);
                
                $media['html'] = [];
                if (preg_match_all('/-iframe-url="(.+?)"/', $footerHTML, $matchList)) {
                    $hrefList = array_unique($matchList[1]);
                    foreach ($hrefList as $href) {
                        $href = sprintf(self::TWITTER_URL_HTML, $href);
                        if ($href = $this->downloadHTML($href)) {
                            $media['html'][] = $href;
                        }
                    }
                }
                /*
                 * $nodeList = $this->xpath->evaluate('.//*[@data-card-name="__entity_video"]/@data-src', $tweetNode);
                 * foreach ($nodeList as $node) {
                 * $href = $this->xpath->evaluate('string(.)', $node);
                 * $href = sprintf(self::TWITTER_URL_HTML, $href);
                 * if ($href = $this->downloadHTML($href)) {
                 * $media['html'][] = $href;
                 * }
                 * }
                 * //
                 */
                
                $media['video'] = [];
                $nodeList = $this->xpath->evaluate('.//video', $tweetNode);
                foreach ($nodeList as $node) {
                    $media['video'][] = $this->xpath->evaluate('string(descendant-or-self::*/@src | descendant-or-self::*/@video-src)', $node);
                }
                $media['video'] = array_unique($media['video']);
                
                $tweet['tweet_media'] = json_encode($media);
                
                if ($tweet['tweet_time'] and strlen($tweet['tweet_text'])) {
                    $ret = $tweet;
                }
            }
        }
        return $ret;
    }

    protected function downloadTweet($tweetId)
    {
        $ret = null;
        $url = sprintf(self::TWITTER_URL_MEDIA, $tweetId);
        if ($data = $this->downloadJSON($url, true) and isset($data['tweet_html']) and strlen($data['tweet_html'])) {
            $ret = $data;
        }
        return $ret;
    }

    protected function downloadXPath($href, $useCache = false)
    {
        $cacheTime = $useCache ? Seconds::DECADE : Seconds::MINUTE;
        return Storage::loadExternalXPath($href, $cacheTime);
    }

    protected function downloadJSON($href, $useCache = false)
    {
        $cacheTime = $useCache ? Seconds::DECADE : Seconds::MINUTE;
        return Storage::loadExternalJSON($href, $cacheTime);
    }

    protected function downloadImage($href)
    {
        $ret = null;
        $file = basename($href);
        if ($pos = strpos($file, ':')) {
            $file = substr($file, 0, $pos);
        }
        if (strpos($file, '.')) {
            $file = $this->tableName . '.' . $file;
            
            $path = SERVER_ROOT . self::DIR_IMAGES . $file;
            $uri = self::URL_IMAGES . $file;
            
            $ret = $href;
            
            $val = preg_replace('/:[a-z]+$/', '', $href);
            $hrefList = [];
            $hrefList[] = $val . ':orig';
            $hrefList[] = $val . ':large';
            $hrefList[] = $val;
            $hrefList[] = $href;
            $hrefList = array_unique($hrefList);
            foreach ($hrefList as $val) {
                if (HTTPFile::createFromDownload($path, $val, self::HTTP_CACHETIME)) {
                    $ret = $uri;
                    break;
                }
            }
        }
        return $ret;
    }

    protected function downloadHTML($href)
    {
        $ret = null;
        $file = parse_url($href, PHP_URL_PATH);
        $file = basename($file);
        $file = sprintf('%s.%s', $this->tableName, $file);
        
        $path = SERVER_ROOT . self::DIR_HTML . $file . '.html';
        $uri = self::URL_HTML . $file;
        
        if (HTTPFile::createFromDownload($path, $href, self::HTTP_CACHETIME)) {
            $ret = $uri;
        }
        
        if (! $ret) {
            $arr = file(__FILE__ . '.txt');
            $arr[] = $href . PHP_EOL;
            $arr = array_unique($arr);
            file_put_contents(__FILE__ . '.txt', implode('', $arr));
        }
        
        return $ret;
    }

    public function fetchOLD()
    {
        $count = 0;
        $maxId = null;
        $baseURI = sprintf(self::TWITTER_URL_FETCH, $this->userName);
        $tweetList = [];
        do {
            $c = 0;
            $hasMoreItems = false;
            $useCache = false;
            $uri = $baseURI;
            if ($maxId) {
                $uri .= sprintf('&max_position=%s', $maxId);
                $maxId = null;
                $useCache = true;
            }
            
            // echo $uri . PHP_EOL;
            
            if ($data = $this->downloadJSON($uri, $useCache)) {
                $hasMoreItems = $data['has_more_items'];
                $html = $data['items_html'];
                
                if ($fragment = $this->dom->parse($html, $this->doc, true)) {
                    $tmpList = $this->xpath->evaluate('*[@data-item-id]', $fragment);
                    $nodeList = [];
                    foreach ($tmpList as $node) {
                        $nodeList[] = $node;
                    }
                    unset($tmpList);
                    
                    foreach ($nodeList as $node) {
                        if ($node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                        $tweetId = $node->getAttribute('data-item-id');
                        // echo $tweetId . PHP_EOL;
                        $maxId = $tweetId;
                        if (! isset($tweetList[$tweetId])) {
                            $tweetList[$tweetId] = true;
                            
                            $html = $this->dom->stringify($node);
                            $arr = $this->parseNode($node, $tweetId);
                            $arr['tweet_id'] = $tweetId;
                            $arr['tweet_html'] = $html;
                            if ($arr['tweet_time'] > 0 and strlen($arr['tweet_text'])) {
                                try {
                                    if ($this->dbmsTable->insert($arr, $arr)) {
                                        $c ++;
                                    }
                                } catch (Exception $e) {}
                            } else {
                                my_dump($arr);
                                echo PHP_EOL . PHP_EOL;
                                throw new Exception('invalid parsing?');
                            }
                        }
                    }
                }
            }
            $count += $c;
        } while ($maxId and $hasMoreItems);
        return $count;
    }

    protected function parseNodeOLD(DOMNode $tweetNode, $tweetId)
    {
        $tmpList = $this->xpath->evaluate('.//img[@alt]', $tweetNode);
        $nodeList = [];
        foreach ($tmpList as $tmp) {
            $nodeList[] = $tmp;
        }
        foreach ($nodeList as $node) {
            $node->parentNode->replaceChild($node->ownerDocument->createTextNode($node->getAttribute('alt')), $node);
        }
        
        $arr = [];
        $arr['version'] = self::VERSION;
        $arr['tweet_time'] = (int) $this->xpath->evaluate('number(.//@data-time)', $tweetNode);
        $arr['tweet_user'] = $this->xpath->evaluate('string(.//@data-user-id)', $tweetNode);
        $arr['tweet_text'] = $this->xpath->evaluate('string(.//p[contains(@class, "tweet-text")][1])', $tweetNode);
        
        $media = [];
        
        $media['image'] = [];
        $imgList = $this->xpath->evaluate('.//p[1]/following::img', $tweetNode);
        foreach ($imgList as $img) {
            $href = $this->xpath->evaluate('string(@src)', $img);
            $href = $this->downloadImage($href);
            $media['image'][] = $href;
        }
        $url = sprintf(self::TWITTER_URL_MEDIA, $tweetId);
        if ($data = $this->downloadJSON($url, true)) {
            if (isset($data['tweet_html'])) {
                $html = $data['tweet_html'];
                if (preg_match_all('/-url-large=\&quot;(http.+?)\&quot;/', $html, $matchList)) {
                    $hrefList = array_unique($matchList[1]);
                    foreach ($hrefList as $href) {
                        if ($href = $this->downloadImage($href)) {
                            $media['image'][] = $href;
                        }
                    }
                }
                if (preg_match_all('/-url-large="(http.+?)"/', $html, $matchList)) {
                    $hrefList = array_unique($matchList[1]);
                    foreach ($hrefList as $href) {
                        if ($href = $this->downloadImage($href)) {
                            $media['image'][] = $href;
                        }
                    }
                }
                // my_dump([$tweetId => $media]);
            }
        }
        
        $media['video'] = [];
        $videoList = $this->xpath->evaluate('.//p[1]/following::video', $tweetNode);
        foreach ($videoList as $video) {
            $media['video'][] = $this->xpath->evaluate('string(descendant-or-self::*/@src | descendant-or-self::*/@video-src)', $video);
        }
        
        $arr['tweet_media'] = json_encode($media);
        
        return $arr;
    }
}