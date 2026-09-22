<?php

declare(strict_types=1);

namespace ABCD\Plugins\Museum\Audit;

class MovementObserver
{
    public static function auditLocationChange(string $dbName, string $mfn, array $oldRecord, array $newRecord): void
    {
        if ($dbName !== 'spectrum') return;

        $oldLocation = $oldRecord['800'] ?? 'Unknown/New';
        $newLocation = $newRecord['800'] ?? 'Unknown/New';

        if ($oldLocation !== $newLocation) {
            $objectId = $newRecord['001'] ?? "MFN-{$mfn}";
            $user = $_SESSION['login'] ?? 'System';
            self::dispatchWxisAudit($objectId, $oldLocation, $newLocation, $user);
        }
    }

    private static function dispatchWxisAudit(string $objectId, string $origin, string $destination, string $user): void
    {
        global $db_path, $xWxis, $ABCD_scripts_path;

        if (!isset($_SESSION["lang"])) $_SESSION["lang"] = "pt";

        $timestamp = date('Y-m-d H:i:s');
        $salt = "ABCD_MUSEUM_AUDIT_SALT_" . date('Ymd');
        $hash = hash('sha256', $objectId . $origin . $destination . $timestamp . $salt);

        $payload = "d*<10>{$objectId}</10><20>{$timestamp}</20><30>{$origin}</30><40>{$destination}</40><70>{$user}</70><99>{$hash}</99>";
        $IsisScript = $xWxis . "actualizar.xis";
        $cipar = $db_path . "par/spec_movements.par";
        $query = "&base=spec_movements&cipar={$cipar}&Opcion=nuevo&ValorCapturado=" . urlencode($payload);

        ob_start();
        include(rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php");
        ob_end_clean();
    }
}
