<?php
header('Content-Type: text/html; charset=utf-8');
require 'lib/phpQuery.php';

$url        = 'https://bitbucket.org/dashboard/repositories';
$auth_data = [
    'username'  => '*********',
    'password'  => '******'
];

function getContent($url,$auth=[])
{
//    auth in bitbucket.org
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $auth['username'] . ":" . $auth['password']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__.'/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__.'/cookie.txt');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}



function parseToArray($url,$auth_data,$start,$end)
{
    if ($start<$end)
    {
        $data = getContent($url,$auth_data);
        $doc = phpQuery::newDocument($data);
        foreach ($doc->find('.iterable-list tbody tr.iterable-item td a.repo-list--repo-name') as $item)
        {
            $item = pq($item);
            $fp = fopen('data.txt', 'a');
            fwrite($fp, $item->attr('href') . PHP_EOL);
            fclose($fp);
        }
        $next = $doc->find('ol.aui-nav-pagination li.aui-nav-selected')->next()->find('a#page-number-link')->attr('href');
        if (!empty($next))
        {
            $start++;
            parseToArray('https://bitbucket.org'.$next,$auth_data,$start,$end);
        }
        echo $next.' - SUCCESSFULLY<br>';
    }

    return true;
}

$start = 0;
$end = 5;
parseToArray($url,$auth_data,$start,$end);
