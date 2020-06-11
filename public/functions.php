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

include "../src/UserHelper.php";
include "../src/DateHelper.php";
include "../src/Entity/MessageType.php";
include "../src/Entity/Message.php";
include "../src/Entity/Animation.php";
include "../src/Entity/Audio.php";
include "../src/Entity/Contact.php";
include "../src/Entity/Document.php";
include "../src/Entity/Location.php";
include "../src/Entity/Photo.php";
include "../src/Entity/Poll.php";
include "../src/Entity/Sticker.php";
include "../src/Entity/User.php";
include "../src/Entity/Video.php";
include "../src/Entity/Voice.php";
include "../src/Parsers/CachedParser.php";
include "../src/Parsers/ExportParser.php";
include "../src/StatMachine.php";
include "../src/StatHandlers/AbstractHandler.php";
include "../src/StatHandlers/CountTotal.php";
include "../src/StatHandlers/FirstMessage.php";
include "../src/StatHandlers/CountTotalByUser.php";
include "../src/StatHandlers/CountTotalByDate.php";
include "../src/StatHandlers/StrlenByUser.php";
include "../src/StatHandlers/UserMedianMessageLength.php";
include "../src/StatHandlers/TotalStrlen.php";
include "../src/StatHandlers/CountRepliesByUser.php";
include "../src/StatHandlers/CountUsersByDayNHours.php";
include "../src/StatHandlers/CountByTypeAndUser.php";
include "../src/StatHandlers/PopularWordsCount.php";
include "../src/StatHandlers/MedianByDate.php";
include "../src/StatHandlers/CountTotalUsers.php";

function makeData($dir, $title, $dataKey, $cache, $cacheRebuild, $debug, Stopwatch $w) {
    $cachedFile = dirname(__DIR__) . '/var/cache/parsers/' . $dataKey .'.json';

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
