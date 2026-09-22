<?php
/*
 * Script: Museum Module Bootstrap
 */

declare(strict_types=1);

namespace ABCD\Plugins\Museum;

use ABCD\Common\LanguageManager;

// Verificação de Instalação Silenciosa
$installLockFile = __DIR__ . '/.installed_lock';

// 1. Register the module in the Top Navigation Bar
abcd_add_hook('abcd_topbar_modules', function (array $modules): array {
    global $msgstr, $langManager;

    if (isset($langManager)) {
        $userLang = $_SESSION["lang"] ?? $_REQUEST["lang"] ?? 'en';
        $plugin_lang = $langManager->loadPluginTranslations(__DIR__, 'museum', 'museum.tab', $userLang);

        if (is_array($msgstr) && is_array($plugin_lang)) {
            $msgstr = array_merge($msgstr, $plugin_lang);
        }
    }

    $hasPermission = false;
    if (isset($_SESSION["permiso"])) {
        if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) || isset($_SESSION["permiso"]["MUSEUM_ALL"]) || ($_SESSION["profile"] ?? '') === 'adm') {
            $hasPermission = true;
        }
    }

    $modules['museum'] = [
        'title'  => $msgstr['museum_module'] ?? 'Museum',
        'icon'   => 'fas fa-landmark',
        'action' => '/content/plugins/museum/index.php',
        'params' => [
            'base' => $_REQUEST['base'] ?? 'spectrum',
            'lang' => $userLang
        ],
        'perm'   => $hasPermission,
        'active' => (isset($_SESSION["MODULO"]) && $_SESSION["MODULO"] === "museum")
    ];

    return $modules;
});

// 2. Register the Event Listener using a Closure
abcd_add_hook('abcd_after_save_record', function (string $dbName, string $mfn, array $oldRecord, array $newRecord): void {
    $observerFile = __DIR__ . '/src/Audit/MovementObserver.php';
    if (file_exists($observerFile)) {
        require_once $observerFile;
        \ABCD\Plugins\Museum\Audit\MovementObserver::auditLocationChange($dbName, $mfn, $oldRecord, $newRecord);
    }
}, 10, 4);
