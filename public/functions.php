<?php

use ChatStats\Parsers\CachedParser;
use ChatStats\Parsers\ExportParser;
use ChatStats\StatHandlers\CountByTypeAndUser;
use ChatStats\StatHandlers\CountRepliesByUser;
use ChatStats\StatHandlers\CountTotal;
use ChatStats\StatHandlers\CountTotalByDate;
use ChatStats\StatHandlers\CountTotalByUser;
use ChatStats\StatHandlers\CountTotalUsers;
use ChatStats\StatHandlers\CountUsersByDayNHours;
use ChatStats\StatHandlers\FirstMessage;
use ChatStats\StatHandlers\MedianByDate;
use ChatStats\StatHandlers\PopularWordsCount;
use ChatStats\StatHandlers\StrlenByUser;
use ChatStats\StatHandlers\TotalStrlen;
use ChatStats\StatHandlers\UserMedianMessageLength;
use ChatStats\StatMachine;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\VarDumper;
use Tightenco\Collect\Support\LazyCollection;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

function makeData($dir, $title, $dataKey, $cache, $cacheRebuild, $debug, Stopwatch $w) {
    $cachedFile = dirname(__DIR__) . '/var/cache/parsers/' . $dataKey .'.json';
    if (!file_exists($dir)) return;
    // TODO: склонение всяких слов и приведение их к общему знаменателю
    // TODO: команда для симфоней
    // TODO: почистить шаблоны
    // TODO: глобальный режим дебага. В том числе для твига

    $parser = new CachedParser(new ExportParser($dir), $cachedFile, $cache, $cacheRebuild);
    $loader = new FilesystemLoader(dirname(__DIR__) . '/src/templates/default');
    $twig = new Environment($loader, [
        'cache' => dirname(__DIR__) . '/var/twig/cache',
        'debug' => !$debug,
        'auto_reload' => !$debug,
    ]);

    $twig->addFilter(new TwigFilter('md5', 'md5'));

    $Stats = new StatMachine(
        LazyCollection::make($parser->getMessages()),
        $w,
        [
            new FirstMessage(),
            new CountTotal(),
            new TotalStrlen(),
            new MedianByDate(),
            new CountTotalByUser(),
            new StrlenByUser(),
            new UserMedianMessageLength(),
            new CountTotalUsers(),
            new CountTotalByDate(),
            new CountRepliesByUser(),
            new CountUsersByDayNHours(),
            new CountByTypeAndUser(),
            new PopularWordsCount(),
        ],
        $twig
    );

    $Stats->calculate();

    $data = $Stats->getStats();

    $Stats->render(
        dirname(__DIR__) . '/var/html/' . $dataKey . '.html',
        $title
    );
}

function dumpTime(Stopwatch $stopwatch, $debug) {

    if (!$debug) return;
    VarDumper::dump("-------------");
    foreach ($stopwatch->getSections() as $name => $section) {
        VarDumper::dump($name);
        VarDumper::dump('--------');
        foreach ($section->getEvents() as $evName => $event) {
            VarDumper::dump('--' . $evName);
            VarDumper::dump('--' . $event->getDuration() / 1000 . ' sec');
            VarDumper::dump('--' . $event->getMemory() / 1024 . ' Kb');
        }
        VarDumper::dump('===========');
    }

}
