<?php
ini_set('memory_limit', '1G');

require_once "../vendor/autoload.php";

use Symfony\Component\Stopwatch\Stopwatch;

$cache = true;
$cacheRebuild = false;
$debug = false;
$w = new Stopwatch();

makeData(__DIR__ . "/data/test", "Статистика по чатику", 'test', $cache, $cacheRebuild, $debug, $w);
dumpTime($w, $debug);


