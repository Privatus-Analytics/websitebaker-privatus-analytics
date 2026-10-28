<?php
/**
 * Helpers for the Privatus Analytics module. No WebsiteBaker calls in here,
 * so the tests can load this file on its own.
 *
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH') && !defined('PRIVATUS_ANALYTICS_TESTING')) { header('HTTP/1.1 404 Not Found'); exit; }

if (!class_exists('PrivatusAnalyticsWb', false)) {
    class PrivatusAnalyticsWb
    {
        const SCRIPT_URL = 'https://privatusstats.com/js/pa.js';
        const MODULES = ['engage', 'auto', 'vitals', 'clicks', 'ask'];
        const SETTING_SITE_ID = 'privatus_analytics_site_id';
        const SETTING_MODULES = 'privatus_analytics_modules';
        const SETTING_SKIP_LOGGED_IN = 'privatus_analytics_skip_logged_in';

        public static function isValidSiteId($sSiteId)
        {
            return is_string($sSiteId) && preg_match('/^pa_[A-Za-z0-9]+$/D', $sSiteId) === 1;
        }

        /** Keeps known modules only, in the standard order. */
        public static function cleanModules($mModules)
        {
            if (is_string($mModules)) {
                $mModules = explode(',', $mModules);
            }
            if (!is_array($mModules)) {
                return [];
            }
            $aWanted = array_map('strval', $mModules);
            return array_values(array_filter(self::MODULES, function ($sModule) use ($aWanted) {
                return in_array($sModule, $aWanted, true);
            }));
        }

        /** The script tag, or '' when the site ID is not valid. */
        public static function buildTag($sSiteId, $mModules)
        {
            if (!self::isValidSiteId($sSiteId)) {
                return '';
            }
            $aModules = self::cleanModules($mModules);
            return '<script defer src="' . self::SCRIPT_URL . '"'
                . ' data-site="' . htmlspecialchars($sSiteId, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-modules="' . htmlspecialchars(implode(',', $aModules), ENT_QUOTES, 'UTF-8') . '"></script>';
        }

        /** Puts the tag just before the first </head>. Pages with no head, or with the script already, stay as they are. */
        public static function addToHead($sHtml, $sTag)
        {
            if ($sTag === '' || !is_string($sHtml) || strpos($sHtml, self::SCRIPT_URL) !== false) {
                return $sHtml;
            }
            $iPos = stripos($sHtml, '</head>');
            if ($iPos === false) {
                return $sHtml;
            }
            return substr($sHtml, 0, $iPos) . $sTag . "\n" . substr($sHtml, $iPos);
        }
    }
}
