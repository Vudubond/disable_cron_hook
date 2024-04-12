#!/usr/local/cpanel/3rdparty/bin/php -q
<?php

/**
 * @version    1.0.0
 * @package    Disable Certain Cronjobs
 * @author     Vudubond
 * @url
 * @copyright
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */

// file - /usr/local/src/vudubond3/disable_cron_hook.php

// Save hook action scripts in the /usr/local/cpanel/3rdparty/bin directory.
// Scripts must have root:root ownership and 755 permissions.
// Hook modules execute as part of the cPanel Server daemon (cpsrvd).
// Hook action code as a script cannot access cPanel environment variables.

// PHP Log
// /usr/local/cpanel/logs/error_log

// Registered hooks
// /usr/local/cpanel/bin/manage_hooks list

// Toggle debug mode
// Debug Mode option in the Development section of WHM's Tweak Settings (WHM >> Home >> Server Configuration >> Tweak Settings)

// Install
// mkdir /usr/local/src/vudubond3
// cd /usr/local/src/vudubond3;
// https://raw.githubusercontent.com/Vudubond/disable_cron_hook/master/disable_cron_hook.php
// copy file to folder
// chown root:root /usr/local/src/vudubond3/disable_cron_hook.php;
// chmod 755 /usr/local/src/vudubond3/disable_cron_hook.php;
// /usr/local/cpanel/bin/manage_hooks add script /usr/local/src/vudubond3/disable_cron_hook.php
// create and populate the file /etc/disabled_cronjobs.txt
// touch /etc/disabled_cronjobs.txt

// Uninstall
// /usr/local/cpanel/bin/manage_hooks delete script /usr/local/src/vudubond3/disable_cron_hook.php


// Embed hook attribute information
function describe()
{
    $api2_add_hook = array(
        'blocking' => 1,
        'category' => 'Cpanel',
        'event'    => 'Api2::Cron::add_line',
        'stage'    => 'pre',
        'hook'     => '/usr/local/src/vudubond3/disable_cron_hook.php --add_api2',
        'exectype' => 'script',
    );

    $uapi_add_hook = array(
        'blocking' => 1,
        'category' => 'Cpanel',
        'event'    => 'UAPI::Cron::add_line',
        'stage'    => 'pre',
        'hook'     => '/usr/local/src/vudubond3/disable_cron_hook.php --add_uapi',
        'exectype' => 'script',
    );

    return array($api2_add_hook, $uapi_add_hook);
}

function add_uapi($input = array())
{
    return add($input, 'uapi');
}

function add_api2($input = array())
{
    return add($input, 'api2');
}

// Process data from STDIN
function get_passed_data()
{
    // Get input from STDIN
    $raw_data = '';
    $stdin_fh = fopen('php://stdin', 'r');
    if (is_resource($stdin_fh)) {
        stream_set_blocking($stdin_fh, 0);
        while (($line = fgets($stdin_fh, 1024)) !== false) {
            $raw_data .= trim($line);
        }
        fclose($stdin_fh);
    }

    // Process and JSON-decode the raw output
    if ($raw_data) {
        $input_data = json_decode($raw_data, true);
    } else {
        $input_data = array('context' => array(), 'data' => array(), 'hook' => array());
    }

    // Return the output
    return $input_data;
}

function add($input, $api_type)
{

    $api_function = 'uapi' === $api_type ? 'UAPI::Email::store_filter' : 'Api2::Email::storefilter';
    $input_context = $input['context'];
    $input_args = $input['data']['args'];
    //$email_from = $input_args['email'];
    $cron_command = trim($input_args['command']);

    // Initialize result and message variables
         $result = 1;
         $message = '';

//$badcrons = file('/etc/disabled_cronjobs.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$file_content = file_get_contents('/etc/disabled_cronjobs.txt');

// Explode both the cron command and file content into arrays of words
    $cron_command_words = preg_split('/[\s\/]+/', $cron_command);
    $file_content_words = preg_split('/[\s\/]+/', $file_content);
// Check if any part of the cron command matches any part of the content in the file
    foreach ($cron_command_words as $word) {
        if (in_array($word, $file_content_words)) {
            $result = 0;
            $message = "Cronjob creation is disabled for this command: {$cron_command}";
            break; // Stop the loop if a match is found
        }
    }
    // Return the hook result and message
    return array($result, $message);
}


// Any switches passed to this script
$switches = (count($argv) > 1) ? $argv : array();

// Argument evaluation
if (in_array('--describe', $switches)) {
    echo json_encode(describe());
    exit;
} elseif (in_array('--add_api2', $switches)) {
    $input = get_passed_data();
    list($result, $message) = add_api2($input);
    echo "$result $message";
    exit;
} elseif (in_array('--add_uapi', $switches)) {
    $input = get_passed_data();
    list($result, $message) = add_uapi($input);
    echo "$result $message";
    exit;
} else {
    echo '0 /usr/local/src/vudubond3/disable_cron_hook.php needs a valid switch';
    exit(1);
}
