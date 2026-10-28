<?php
/**
 * Settings page under Admin-Tools.
 *
 * @license MIT (c) 2026 Two Phase LLC
 */
if (!defined('WB_PATH')) { header('HTTP/1.1 404 Not Found'); exit; }

require_once __DIR__ . '/PrivatusAnalytics.php';

require __DIR__ . '/languages/EN.php';
$aPaLang = $MOD_PRIVATUS_ANALYTICS;
if (defined('LANGUAGE') && preg_match('/^[A-Z]{2}$/', LANGUAGE) && LANGUAGE !== 'EN' && is_readable(__DIR__ . '/languages/' . LANGUAGE . '.php')) {
    require __DIR__ . '/languages/' . LANGUAGE . '.php';
    $aPaLang = array_merge($aPaLang, $MOD_PRIVATUS_ANALYTICS);
}

if (!function_exists('privatus_analytics_esc')) {
    function privatus_analytics_esc($sValue)
    {
        return htmlspecialchars((string) $sValue, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('privatus_analytics_save_setting')) {
    function privatus_analytics_save_setting($oDb, $sName, $sValue)
    {
        $oDb->query('REPLACE INTO `' . TABLE_PREFIX . 'settings` (`name`, `value`) VALUES (\''
            . $oDb->escapeString($sName) . '\', \'' . $oDb->escapeString($sValue) . '\')');
    }
}

$oPaDb = $database;
$sPaMessage = '';
$sPaMessageClass = 'w3-pale-green';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['privatus_analytics_save'])) {
    if (!$admin->checkFTAN()) {
        $sPaMessage = $aPaLang['BAD_TOKEN'];
        $sPaMessageClass = 'w3-pale-red';
    } else {
        $sPaSiteId = isset($_POST['privatus_analytics_site_id']) ? trim((string) $_POST['privatus_analytics_site_id']) : '';
        $aPaModules = PrivatusAnalyticsWb::cleanModules(isset($_POST['privatus_analytics_modules']) ? $_POST['privatus_analytics_modules'] : []);
        $sPaSkip = !empty($_POST['privatus_analytics_skip_logged_in']) ? '1' : '0';

        if ($sPaSiteId !== '' && !PrivatusAnalyticsWb::isValidSiteId($sPaSiteId)) {
            $sPaMessage = $aPaLang['BAD_SITE_ID'];
            $sPaMessageClass = 'w3-pale-red';
        } else {
            privatus_analytics_save_setting($oPaDb, PrivatusAnalyticsWb::SETTING_SITE_ID, $sPaSiteId);
            privatus_analytics_save_setting($oPaDb, PrivatusAnalyticsWb::SETTING_MODULES, implode(',', $aPaModules));
            privatus_analytics_save_setting($oPaDb, PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN, $sPaSkip);
            $sPaMessage = $sPaSiteId === '' ? $aPaLang['SAVED_EMPTY'] : $aPaLang['SAVED'];
        }
    }
}

$aPaSettings = [];
if (($oPaResult = $oPaDb->query('SELECT `name`, `value` FROM `' . TABLE_PREFIX . 'settings` WHERE `name` LIKE \'privatus\_analytics\_%\''))) {
    while (($aPaRow = $oPaResult->fetchRow(MYSQLI_ASSOC))) {
        $aPaSettings[$aPaRow['name']] = $aPaRow['value'];
    }
}
$sPaSiteId = isset($aPaSettings[PrivatusAnalyticsWb::SETTING_SITE_ID]) ? $aPaSettings[PrivatusAnalyticsWb::SETTING_SITE_ID] : '';
$aPaModules = PrivatusAnalyticsWb::cleanModules(isset($aPaSettings[PrivatusAnalyticsWb::SETTING_MODULES]) ? $aPaSettings[PrivatusAnalyticsWb::SETTING_MODULES] : PrivatusAnalyticsWb::MODULES);
$bPaSkip = !isset($aPaSettings[PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN]) || $aPaSettings[PrivatusAnalyticsWb::SETTING_SKIP_LOGGED_IN] === '1';

$sPaAction = ADMIN_URL . '/admintools/tool.php?tool=privatus_analytics';
?>
<div class="privatus-analytics-settings" style="max-width:46em">
<?php if ($sPaMessage !== '') { ?>
  <p class="w3-panel <?php echo $sPaMessageClass; ?>" style="padding:.6em 1em"><?php echo privatus_analytics_esc($sPaMessage); ?></p>
<?php } ?>
  <p><?php echo privatus_analytics_esc($aPaLang['INTRO']); ?></p>
  <form method="post" action="<?php echo privatus_analytics_esc($sPaAction); ?>">
    <?php echo $admin->getFTAN(); ?>
    <input type="hidden" name="tool" value="privatus_analytics">
    <input type="hidden" name="privatus_analytics_save" value="1">
    <p>
      <label for="privatus_analytics_site_id"><strong><?php echo privatus_analytics_esc($aPaLang['SITE_ID']); ?></strong></label><br>
      <input type="text" id="privatus_analytics_site_id" name="privatus_analytics_site_id" value="<?php echo privatus_analytics_esc($sPaSiteId); ?>" placeholder="pa_XXXXXXXXXX" pattern="pa_[A-Za-z0-9]+" style="width:20em" autocomplete="off"><br>
      <small><?php echo privatus_analytics_esc($aPaLang['SITE_ID_HELP']); ?></small>
    </p>
    <fieldset style="border:0;padding:0;margin:0 0 1em">
      <legend><strong><?php echo privatus_analytics_esc($aPaLang['MODULES']); ?></strong></legend>
<?php foreach (PrivatusAnalyticsWb::MODULES as $sPaModule) { ?>
      <label style="display:block"><input type="checkbox" name="privatus_analytics_modules[]" value="<?php echo privatus_analytics_esc($sPaModule); ?>"<?php echo in_array($sPaModule, $aPaModules, true) ? ' checked' : ''; ?>> <?php echo privatus_analytics_esc($aPaLang['MODULE_' . strtoupper($sPaModule)]); ?></label>
<?php } ?>
    </fieldset>
    <p><label><input type="checkbox" name="privatus_analytics_skip_logged_in" value="1"<?php echo $bPaSkip ? ' checked' : ''; ?>> <?php echo privatus_analytics_esc($aPaLang['SKIP_LOGGED_IN']); ?></label></p>
    <p><button type="submit" class="w3-btn w3-blue-wb"><?php echo privatus_analytics_esc($aPaLang['SAVE']); ?></button></p>
  </form>
  <p><small><?php echo privatus_analytics_esc($aPaLang['NO_ACCOUNT']); ?> <a href="https://privatusanalytics.com" target="_blank" rel="noopener"><?php echo privatus_analytics_esc($aPaLang['START_FREE']); ?></a></small></p>
</div>
