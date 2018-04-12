<?php
ini_set('max_execution_time', 600);
header('Content-Type: text/html; charset=utf-8');
require 'lib/phpQuery.php';

$url       = 'https://bitbucket.org/dashboard/repositories';
$auth_data = [
    'username'  => '*****',
    'password'  => '*****'
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

//парсим ссылки и сохраняем в data.txt
function parseToTxt($url,$auth_data,$start,$end)
{
    if ($start<$end)
    {
        $data = getContent($url,$auth_data);
        $doc = phpQuery::newDocument($data);
        foreach ($doc->find('.iterable-list tbody tr.iterable-item td a.repo-list--repo-name') as $item)
        {
            $item = pq($item);
            $fp = fopen('data.txt', 'a');
            fwrite($fp, 'https://bitbucket.org'.$item->attr('href').'/downloads/' . PHP_EOL);
            fclose($fp);
            echo "<code>parse ...".$item->attr('href')."</code><br>";
        }
        $next = $doc->find('ol.aui-nav-pagination li.aui-nav-selected')->next()->find('a#page-number-link')->attr('href');
        if (!empty($next))
        {
            $start++;
            parseToTxt('https://bitbucket.org'.$next,$auth_data,$start,$end);
        }
    }
    return true;
}

//сохраняем репозитории в result
function parseArchive($auth_data)
{
    $urls = file('data.txt');
    if (!empty($urls))
    {
        foreach ($urls as $url)
        {
            $url = rtrim($url);
            $data = getContent($url,$auth_data);
            $doc = phpQuery::newDocument($data);
            $name = $doc->find('ol.aui-nav-breadcrumbs li:last-child a')->text();
            $name = str_replace(' ','_',$name);
            $href = $doc->find('table#uploaded-files tr.download-repo a.lfs-warn-link')->attr('href');
            $href = 'https://bitbucket.org'.$href;
            $path = $_SERVER['DOCUMENT_ROOT'] . '/result/'.$name.'.zip';
            curl_download($href,$path,$auth_data);
            echo "<code><i>Load ".$name."</i></code><br>";
        }
    }else{
        echo 'EMPTY data.txt!';
    }

}

function curl_download($url, $file, $auth)
{
    // открываем файл, на сервере, на запись
    $dest_file = @fopen($file, "w");
    // открываем cURL-сессию
    $resource = curl_init();
    // устанавливаем опцию удаленного файла
    curl_setopt($resource, CURLOPT_URL, $url);
    // авторизируемся на сайте
    curl_setopt($resource, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($resource, CURLOPT_USERPWD, $auth['username'] . ":" . $auth['password']);
    curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($resource, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($resource, CURLOPT_COOKIEJAR, __DIR__.'/cookie.txt');
    curl_setopt($resource, CURLOPT_COOKIEFILE, __DIR__.'/cookie.txt');
    curl_setopt($resource, CURLOPT_TIMEOUT, 30);
    // устанавливаем место на сервере, куда будет скопирован удаленной файл
    curl_setopt($resource, CURLOPT_FILE, $dest_file);
    // заголовки нам не нужны
    curl_setopt($resource, CURLOPT_HEADER, 0);
    // выполняем операцию
    curl_exec($resource);
    // закрываем cURL-сессию
    curl_close($resource);
    // закрываем файл
    fclose($dest_file);
}

//----------------------------------------------------------------------------------------
//количество станиц пагинации
$start = 0;
$end = 5;

//получаем и записываем ссылки в data.txt
echo "<code><strong>Start...</strong></code> <br>";
parseToTxt($url,$auth_data,$start,$end);
echo "<code><strong>End parse url's</strong></code> <br><hr><br>";

//скачиваем репозитории
echo "<code><strong>Start download repositories...</strong></code> <br>";
//parseArchive($auth_data);
echo "<code><strong>END.</strong></code> <br>";
//----------------------------------------------------------------------------------------