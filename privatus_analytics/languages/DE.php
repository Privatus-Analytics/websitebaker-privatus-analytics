<?php
/**
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

$MOD_PRIVATUS_ANALYTICS = [
    'INTRO' => 'Privatus Analytics zählt echte Besucher ohne Cookies. Fügen Sie Ihre Site-ID ein und speichern Sie. Dann steht das Skript im Head jeder Seite.',
    'SITE_ID' => 'Site-ID',
    'SITE_ID_HELP' => 'Sie beginnt mit pa_. Sie finden sie in Privatus Analytics unter Site settings, Tracking.',
    'MODULES' => 'Zusätzliches Tracking',
    'MODULE_ENGAGE' => 'Zeit auf der Seite und Scrolltiefe',
    'MODULE_AUTO' => 'Klicks auf externe Links, Downloads, gesendete Formulare und 404-Seiten',
    'MODULE_VITALS' => 'Ladezeit (Core Web Vitals)',
    'MODULE_CLICKS' => 'Klickkarten',
    'MODULE_ASK' => 'Kurze Umfragen auf der Website',
    'SKIP_LOGGED_IN' => 'Meine eigenen Besuche nicht zählen, solange ich angemeldet bin',
    'SAVE' => 'Speichern',
    'SAVED' => 'Einstellungen gespeichert.',
    'SAVED_EMPTY' => 'Einstellungen gespeichert. Es ist keine Site-ID gesetzt, also wird kein Skript eingefügt.',
    'BAD_SITE_ID' => 'Diese Site-ID sieht nicht richtig aus. Sie muss mit pa_ beginnen und darf danach nur Buchstaben und Ziffern enthalten.',
    'BAD_TOKEN' => 'Das Formular ist abgelaufen. Bitte versuchen Sie es erneut.',
    'NO_ACCOUNT' => 'Noch kein Konto? Der kostenlose Tarif bietet 10.000 Events im Monat für bis zu 3 Websites, ohne Kreditkarte.',
    'START_FREE' => 'Kostenlos starten auf privatusanalytics.com',
];
