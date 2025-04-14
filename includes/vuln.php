<?php
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('plugin-scan vuln', 'Plugin_Scan_Command');
}

class Plugin_Scan_Command {

    public function __invoke($args, $assoc_args) {
        $plugin_dir = plugin_dir_path(__FILE__);
        $scanner = escapeshellcmd($plugin_dir . 'php-malware-scanner/scan.php');
    
        if (!file_exists($scanner)) {
            WP_CLI::error("Scanner not found at $scanner");
        }
    
        $cmd_parts = ["php", escapeshellarg($scanner)];
    
        if (!empty($args)) {
            $target_path = realpath(ABSPATH . $args[0]);
    
            if (!$target_path || !file_exists($target_path)) {
                WP_CLI::error("Target not found: " . $args[0]);
            }
    
            // Pass file or dir directly to scanner
            $cmd_parts[] = "-d";
            $cmd_parts[] = escapeshellarg($target_path);
        } else {
            // Default folders to scan
            $folders = [
                ABSPATH . 'wp-includes/',
                ABSPATH . 'wp-admin/',
                ABSPATH . 'wp-content/plugins/',
                ABSPATH . 'wp-content/themes/',
            ];
    
            foreach ($folders as $folder) {
                if (file_exists($folder)) {
                    $cmd_parts[] = "-d";
                    $cmd_parts[] = escapeshellarg($folder);
                }
            }
        }
    
        // Optional flags
        $cmd_parts[] = "--all-output";
        $cmd_parts[] = "--hide-ok";
        $cmd_parts[] = "--line-number";
        $cmd_parts[] = "--hide-whitelist";
        $cmd_parts[] = "--base64";
    
        $cmd = implode(' ', $cmd_parts);
    
        WP_CLI::log("Running malware scan...");
        passthru($cmd, $exit_code);
    
        if ($exit_code === 0) {
            WP_CLI::success("Scan completed.");
        } else {
            WP_CLI::warning("Scan finished with some issues. Review the results above.");
        }
    }    
}