<?php

/*
Plugin Name:  wpmtg
Description:  A Magic: The Gathering plugin
Plugin URI:   https://dustinsmodern.life
Author:       Dustin Hein
Version:      0.1.20
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

// disable direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Register the autoloader for the plugin.
include_once __DIR__ . '/autoloader.php';

use Wpmtg\Wpmtg;

$wpmtg = new Wpmtg();
