<?php
ini_set('memory_limit', '1G');

require_once "../vendor/autoload.php";
include "functions.php";

use Symfony\Component\Stopwatch\Stopwatch;

$cache = true;
$cacheRebuild = false;
$debug = false;
$w = new Stopwatch();

makeData(__DIR__ . "/data/kukuTest", "Статистика по чатику K y k y u з M", 'kukuTest', $cache, $cacheRebuild, $debug, $w);
makeData(__DIR__ . "/data/kuku", "Статистика по чатику K y k y u з M", 'kuku', $cache, $cacheRebuild, $debug, $w);
makeData(__DIR__ . "/data/sts", "Статистика по чатику СТС", 'sts', $cache, $cacheRebuild, $debug, $w);

dumpTime($w, $debug);


