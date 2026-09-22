<?php
declare(strict_types=1);
namespace ABCD\Plugins\Museum\Setup;

class DatabaseInstaller
{
    public static function runSilentInstall(): void
    {
        global $db_path, $xWxis, $ABCD_scripts_path;
        
        $museum_databases = [
            'spec_receipts' => ['description' => 'Museum Entry and Exit Receipts', 'fdt' => ["10|Type|1|0", "20|Date|1|0", "30|Owner|1|0"]],
            'spec_movements' => ['description' => 'Location Audit Trail', 'fdt' => ["10|Object ID|1|0", "20|Timestamp|1|0", "30|Origin|1|0", "40|Destination|1|0"]],
            'spec_loans' => ['description' => 'Loans and Acquisitions', 'fdt' => ["10|Type|1|0", "20|Object ID|1|0", "30|Institution|1|0"]]
        ];

        $bases_dat = $db_path . "bases.dat";
        $bases_registered = file_exists($bases_dat) ? file_get_contents($bases_dat) : '';

        foreach ($museum_databases as $db_name => $db_data) {
            $base_dir = rtrim($db_path, '/\\') . DIRECTORY_SEPARATOR . $db_name;
            
            if (!is_dir($base_dir)) {
                mkdir($base_dir . '/data', 0775, true);
                mkdir($base_dir . '/def/en', 0775, true);
                mkdir($base_dir . '/pfts/en', 0775, true);
                mkdir($base_dir . '/pfts/pt', 0775, true);
                mkdir($base_dir . '/pfts/es', 0775, true);

                $fdt_content = implode("\n", $db_data['fdt']);
                file_put_contents($base_dir . '/def/en/' . $db_name . '.fdt', $fdt_content);
                file_put_contents($base_dir . '/def/' . $db_name . '.fdt', $fdt_content);
                
                $pft_content = "v10, ' - ', v20/"; 
                file_put_contents($base_dir . '/pfts/en/' . $db_name . '.pft', $pft_content);

                $par_content = "{$db_name}.*={$base_dir}/data/{$db_name}.*";
                file_put_contents($db_path . "par/{$db_name}.par", $par_content);

                $query = "&base={$db_name}&cipar={$db_path}par/{$db_name}.par&Opcion=inicializar";
                ob_start();
                include(rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php");
                ob_end_clean();
            }

            // Registra no bases.dat apenas se não existir
            if (strpos($bases_registered, $db_name) === false) {
                file_put_contents($bases_dat, "\n{$db_name}|{$db_data['description']}", FILE_APPEND);
            }
        }
    }
}