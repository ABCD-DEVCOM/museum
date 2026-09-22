<?php

declare(strict_types=1);

namespace ABCD\Plugins\Museum\Audit;

/**
 * Class MovementObserver
 * Intercepts physical location changes in the 'spectrum' database 
 * and securely logs them into 'spec_movements' via CISIS/WXIS.
 */
class MovementObserver
{
    /**
     * Hook callback triggered after saving any record.
     */
    public static function auditLocationChange(string $dbName, string $mfn, array $oldRecord, array $newRecord): void
    {
        // Domain Isolation
        if ($dbName !== 'spectrum') {
            return;
        }

        // Assuming tag 800 stores the physical location
        $oldLocation = $oldRecord['800'] ?? 'Unknown/New';
        $newLocation = $newRecord['800'] ?? 'Unknown/New';

        if ($oldLocation !== $newLocation) {
            $objectId = $newRecord['001'] ?? "MFN-{$mfn}";
            $user = $_SESSION['login'] ?? 'System';

            self::dispatchWxisAudit($objectId, $oldLocation, $newLocation, $user);
        }
    }

    /**
     * Executes the backend WXIS call to append the audit trail.
     */
    private static function dispatchWxisAudit(string $objectId, string $origin, string $destination, string $user): void
    {
        // Import ABCD native globals required by wxis_llamar.php
        global $db_path, $xWxis, $Wxis, $wxisUrl, $postMethod, $cgibin_path, $meta_encoding, $server_url, $ABCD_scripts_path;

        // Ensure we don't break session language during background processing
        if (!isset($_SESSION["lang"])) {
            $_SESSION["lang"] = "pt";
        }

        $timestamp = date('Y-m-d H:i:s');
        $securitySalt = "ABCD_MUSEUM_AUDIT_SALT_" . date('Ymd');
        $securityHash = hash('sha256', $objectId . $origin . $destination . $timestamp . $securitySalt);

        // Formatting CISIS tags: d* deletes existing (for safety in 'nuevo')
        $wxisPayload = "d*<10>{$objectId}</10><20>{$timestamp}</20><30>{$origin}</30><40>{$destination}</40><70>{$user}</70><99>{$securityHash}</99>";

        $IsisScript = $xWxis . "actualizar.xis";
        $cipar = $db_path . "par/spec_movements.par";
        $query = "&base=spec_movements&cipar={$cipar}&Opcion=nuevo&ValorCapturado=" . urlencode($wxisPayload);

        // Execute WXIS silently 
        ob_start();
        $wxis_llamar_path = rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php";
        if (file_exists($wxis_llamar_path)) {
            include($wxis_llamar_path);
        }
        ob_end_clean();

        if (isset($err_wxis) && $err_wxis !== "") {
            error_log("Museum Plugin WXIS Error [Audit Trail]: " . $err_wxis);
        }
    }
}
