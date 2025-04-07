<?php

if (defined('WP_CLI') && WP_CLI) {
    class ECPluginScanCLI {

        private $plugin_info_cache = [];

        public function run($args, $assoc_args) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            WP_CLI::log("\n" . WP_CLI::colorize("%W==== CHECKING FOR CLOSED PLUGINS ====%n\n"));

            $all_plugins = get_plugins();
            $closed_plugins = [];

            foreach ($all_plugins as $plugin_slug => $plugin_data) {
                $slug = $this->extract_slug($plugin_slug);
                $plugin_info = $this->get_plugin_info($slug);

                if (isset($plugin_info['error'])) {
                    if ($plugin_info['error'] === 'closed') {
                        $reason = $plugin_info['reason_text'] ?? 'No reason provided';
                        WP_CLI::log(WP_CLI::colorize("%RPlugin: {$plugin_data['Name']} is closed. Reason: {$reason}%n"));
                        $closed_plugins[] = [
                            'Plugin' => $plugin_data['Name'],
                            'Reason' => $reason,
                        ];
                    } 
                    // elseif ($plugin_info['error'] === 'Plugin not found.') {
                    //     WP_CLI::log(WP_CLI::colorize("%YWarning: Plugin '{$plugin_data['Name']}' not found in the WordPress repo.%n"));
                    // }
                }
            }

            if (empty($closed_plugins)) {
                WP_CLI::success("No closed plugins detected.");
            }

            WP_CLI::log("\n" . WP_CLI::colorize("%W==== SCANNING PLUGINS DIR ====%n\n"));
            $this->scan_plugin_dirs($all_plugins);

            WP_CLI::log("\n" . WP_CLI::colorize("%W==== VERIFY CHECKSUMS ====%n\n"));
            $this->verify_plugin_checksums($all_plugins);
        }

        private function scan_plugin_dirs($all_plugins) {
            $plugin_dir = WP_PLUGIN_DIR;

            $plugin_folders = array_filter(glob($plugin_dir . '/*'), 'is_dir');
            $folder_names = array_map('basename', $plugin_folders);

            $top_level_files = glob($plugin_dir . '/*.php');
            $top_level_plugins = [];

            foreach ($top_level_files as $file_path) {
                $plugin_data = get_plugin_data($file_path, false, false);
                if (!empty($plugin_data['Name'])) {
                    $top_level_plugins[] = basename($file_path);
                }
            }

            $detected_plugin_locations = array_merge($folder_names, $top_level_plugins);

            $plugin_files = array_keys($all_plugins);
            $registered_locations = array_unique(array_map([$this, 'extract_slug'], $plugin_files));

            $total_detected = count($detected_plugin_locations);
            $total_registered = count($registered_locations);

            if ($total_detected !== $total_registered) {
                $extra_items = array_diff($detected_plugin_locations, $registered_locations);
                WP_CLI::warning("Mismatch detected: $total_detected plugin items found vs $total_registered registered plugins.");
                if (!empty($extra_items)) {
                    WP_CLI::log(WP_CLI::colorize("%YExtra plugin folders/files (not registered by WordPress):%n"));
                    foreach ($extra_items as $item) {
                        WP_CLI::log(" - $item");
                    }
                }
            } else {
                WP_CLI::success("Plugin folder/file count matches installed plugins.");
            }
        }

        private function verify_plugin_checksums($all_plugins) {

            $failed_plugins = [];

            foreach ($all_plugins as $plugin_slug => $plugin_data) {
                $slug = $this->extract_slug($plugin_slug);
                $plugin_info = $this->get_plugin_info($slug);

                if (isset($plugin_info['error']) || empty($plugin_info['download_link'])) {
                    WP_CLI::log(WP_CLI::colorize("%BSkipping checksum for non-repo plugin: {$plugin_data['Name']}%n"));
                    continue;
                }

                $result = WP_CLI::runcommand("plugin verify-checksums $slug", ['return' => true, 'exit_error' => false]);

                if (strpos($result, 'Verified 1 of 1 plugins.') === false) {
                    WP_CLI::log(WP_CLI::colorize("%RChecksum failed for plugin: {$plugin_data['Name']}%n"));
                    $failed_plugins[] = $plugin_data['Name'];
                }
            }

            if (empty($failed_plugins)) {
                WP_CLI::success("All plugin checksums verified successfully.");
            }
        }

        private function extract_slug($plugin_path) {
            return explode('/', $plugin_path)[0];
        }

        private function get_plugin_info($slug) {
            if (isset($this->plugin_info_cache[$slug])) {
                return $this->plugin_info_cache[$slug];
            }

            // Optional: cache with WordPress transient for 1 hour
            $cached = get_transient("plugin_info_$slug");
            if ($cached) {
                $this->plugin_info_cache[$slug] = $cached;
                return $cached;
            }

            $response = wp_remote_get("https://api.wordpress.org/plugins/info/1.0/{$slug}.json");
            if (is_wp_error($response)) {
                WP_CLI::warning("Error fetching plugin info for: $slug");
                return [];
            }

            $plugin_info = json_decode(wp_remote_retrieve_body($response), true);
            set_transient("plugin_info_$slug", $plugin_info, HOUR_IN_SECONDS);
            $this->plugin_info_cache[$slug] = $plugin_info;

            return $plugin_info;
        }
    }

    WP_CLI::add_command('plugin-scan', 'ECPluginScanCLI');
}
