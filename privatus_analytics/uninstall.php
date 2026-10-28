<?php
/**
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

$database->query('DELETE FROM `' . TABLE_PREFIX . 'settings` WHERE `name` LIKE \'privatus\_analytics\_%\'');
