<?php
/*
Plugin Name: WP Plugin Inspector
Description: Provides a WP-CLI command to check for outdated or closed plugins.
Version: 1.0.1
Author: Kuzman
*/
if(!defined('ABSPATH')){
    exit;
}

define('PLUGIN_INSPECTOR', plugin_dir_path(__FILE__));

require PLUGIN_INSPECTOR . "includes/cli.php";
require PLUGIN_INSPECTOR . "includes/vuln.php";