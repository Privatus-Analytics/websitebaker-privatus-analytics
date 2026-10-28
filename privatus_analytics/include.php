<?php
/**
 * WebsiteBaker loads include.php of every admin tool before it builds a
 * frontend page. We start an output buffer here, so the finished page passes
 * through addToHead() just before it is sent.
 *
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

require_once __DIR__ . '/PrivatusAnalytics.php';

if (!defined('PRIVATUS_ANALYTICS_LOADED')) {
    define('PRIVATUS_ANALYTICS_LOADED', true);

    $oPaDb = isset($GLOBALS['database']) ? $GLOBALS['database'] : null;
    $oPaWb = isset($GLOBALS['wb']) ? $GLOBALS['wb'] : null;

    if (is_object($oPaDb) && ($oPaWb instanceof \frontend)) {
        $aPaSettings = [];
        $sPaSql = 'SELECT `name`, `value` FROM `' . TABLE_PREFIX . 'settings` WHERE `name` LIKE \'privatus\_analytics\_%\'';
        if (($oPaResult = $oPaDb->query($sPaSql))) {
            while (($aPaRow = $oPaResult->fetchRow(MYSQLI_ASSOC))) {
                $aPaSettings[$aPaRow['name']] = $aPaRow['value'];
            }
        }
        $sPaSiteId = isset($aPaSettings[PrivatusAnalyticsWb::SETTING_SITE_ID]) ? $aPaSettings[PrivatusAnalyticsWb::SETTING_SITE_ID] : '';
        $sPaModules = isset($aPaSettings[PrivatusAnalyticsWb::SETTING_MODULES]) ? $aPaSettings[PrivatusAnalyticsWb::SETTING_MODULES] : implode(',', PrivatusAnalyticsWb::MODULES);
        $bPaSkip = !isset($aPaSettings[PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN]) || $aPaSettings[PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN] === '1';
        $bPaLoggedIn = method_exists($oPaWb, 'is_authenticated') && $oPaWb->is_authenticated();

        $sPaTag = PrivatusAnalyticsWb::buildTag($sPaSiteId, $sPaModules);
        if ($sPaTag !== '' && !($bPaSkip && $bPaLoggedIn)) {
            ob_start(function ($sBuffer) use ($sPaTag) {
                return PrivatusAnalyticsWb::addToHead($sBuffer, $sPaTag);
            });
        }
    }
}
