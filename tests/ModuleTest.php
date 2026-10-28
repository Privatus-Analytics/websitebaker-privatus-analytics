<?php
// Plain PHP tests for the helpers. Run: php tests/ModuleTest.php

define('PRIVATUS_ANALYTICS_TESTING', true);
require __DIR__ . '/../privatus_analytics/PrivatusAnalytics.php';

$failures = 0;
function check($name, $expected, $actual)
{
    global $failures;
    if ($expected === $actual) {
        echo "ok   $name\n";
        return;
    }
    $failures++;
    echo "FAIL $name\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
}

$all = 'engage,auto,vitals,clicks,ask';
check('valid site id', true, PrivatusAnalyticsWb::isValidSiteId('pa_Z6RY7F45OA'));
check('empty site id', false, PrivatusAnalyticsWb::isValidSiteId(''));
check('bare prefix', false, PrivatusAnalyticsWb::isValidSiteId('pa_'));
check('quote in site id', false, PrivatusAnalyticsWb::isValidSiteId('pa_x" onload="alert(1)'));
check('trailing newline', false, PrivatusAnalyticsWb::isValidSiteId("pa_abc\n"));
check('not a string', false, PrivatusAnalyticsWb::isValidSiteId(['pa_abc']));

check('full tag', '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_abc123" data-modules="engage,auto,vitals,clicks,ask"></script>', PrivatusAnalyticsWb::buildTag('pa_abc123', $all));
check('modules kept in standard order, unknown dropped', 'engage,vitals,ask', implode(',', PrivatusAnalyticsWb::cleanModules(['ask', 'evil"', 'engage', 'vitals'])));
check('no modules gives empty attribute', '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_abc" data-modules=""></script>', PrivatusAnalyticsWb::buildTag('pa_abc', ''));
check('bad site id gives no tag', '', PrivatusAnalyticsWb::buildTag('pa_x"><script>', $all));

$tag = PrivatusAnalyticsWb::buildTag('pa_abc', 'engage');
check('tag goes before </head>', "<html><head><title>x</title>$tag\n</head><body></body></html>", PrivatusAnalyticsWb::addToHead('<html><head><title>x</title></head><body></body></html>', $tag));
check('upper case HEAD', "<HEAD>$tag\n</HEAD>", PrivatusAnalyticsWb::addToHead('<HEAD></HEAD>', $tag));
check('no head left alone', '{"json":true}', PrivatusAnalyticsWb::addToHead('{"json":true}', $tag));
check('only the first </head>', "<head>$tag\n</head><pre>&lt;/head&gt;</head></pre>", PrivatusAnalyticsWb::addToHead('<head></head><pre>&lt;/head&gt;</head></pre>', $tag));
$already = '<head><script src="https://privatusstats.com/js/pa.js"></script></head>';
check('script already there is not doubled', $already, PrivatusAnalyticsWb::addToHead($already, $tag));
check('empty tag changes nothing', '<head></head>', PrivatusAnalyticsWb::addToHead('<head></head>', ''));

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures failed.\n";
exit($failures === 0 ? 0 : 1);
