<?php
/**
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

require_once __DIR__ . '/PrivatusAnalytics.php';

$aPaDefaults = [
    PrivatusAnalyticsWb::SETTING_SITE_ID => '',
    PrivatusAnalyticsWb::SETTING_MODULES => implode(',', PrivatusAnalyticsWb::MODULES),
    PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN => '1',
];
foreach ($aPaDefaults as $sPaName => $sPaValue) {
    $sPaSql = 'INSERT IGNORE INTO `' . TABLE_PREFIX . 'settings` (`name`, `value`) VALUES (\''
        . $database->escapeString($sPaName) . '\', \'' . $database->escapeString($sPaValue) . '\')';
    $database->query($sPaSql);
}
