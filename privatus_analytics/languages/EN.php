<?php
/**
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

$MOD_PRIVATUS_ANALYTICS = [
    'INTRO' => 'Privatus Analytics counts real visitors with no cookies. Paste your site ID and save. The script is then added to the head of every page.',
    'SITE_ID' => 'Site ID',
    'SITE_ID_HELP' => 'It starts with pa_. You find it in Privatus Analytics under Site settings, Tracking.',
    'MODULES' => 'Extra tracking',
    'MODULE_ENGAGE' => 'Time on page and scroll depth',
    'MODULE_AUTO' => 'Clicks on outside links, file downloads, form sends and 404 pages',
    'MODULE_VITALS' => 'Page speed (Core Web Vitals)',
    'MODULE_CLICKS' => 'Click maps',
    'MODULE_ASK' => 'Short on-site surveys',
    'SKIP_LOGGED_IN' => 'Do not count my own visits while I am logged in',
    'SAVE' => 'Save',
    'SAVED' => 'Settings saved.',
    'SAVED_EMPTY' => 'Settings saved. No site ID is set, so no script is added.',
    'BAD_SITE_ID' => 'That site ID does not look right. It must start with pa_ and then use only letters and numbers.',
    'BAD_TOKEN' => 'The form timed out. Please try again.',
    'NO_ACCOUNT' => 'No account yet? The free plan has 10,000 events a month on up to 3 sites, and needs no card.',
    'START_FREE' => 'Start free at privatusanalytics.com',
];
