<?php
/**
 * HestiaCP Monitor Bridge
 * Author: JAMP Web Solutions
 * License: GPLv3
 * * This script acts as a secure bridge between the mobile app and HestiaCP CLI.
 */

$configFile = __DIR__ . '/secret.key';

// Check if the secret key exists
if (!file_exists($configFile)) {
    header('HTTP/1.1 500 Internal Server Error');
    die(json_encode(["error" => "Security key missing. Please run the installer script."]));
}

$SECRET_KEY = trim(file_get_contents($configFile));

// Verify that the app sent a token
if (!isset($_POST['app_token']) || empty($_POST['app_token'])) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(["error" => "No token provided"]));
}

$clientToken = $_POST['app_token'];
$currentTimeWindow = floor(time() / 30);
$isAuthorized = false;

// Time-based security: Check token against a 5-minute window (compensates for clock drift)
for ($i = -5; $i <= 5; $i++) {
    $expectedHash = hash('sha256', $SECRET_KEY . ($currentTimeWindow + $i));
    if (hash_equals($expectedHash, $clientToken)) {
        $isAuthorized = true;
        break;
    }
}

if (!$isAuthorized) {
    header('HTTP/1.1 401 Unauthorized');
    die(json_encode(["error" => "Invalid security token"]));
}

// Only allow specific safe commands to be executed
$allowedCommands = [
    'v-list-sys-info',
    'v-list-sys-services',
    'v-list-web-domains',
    'v-list-mail-domains',
    'v-restart-service'
];

$cmd = $_POST['cmd'] ?? '';
$arg1 = escapeshellarg($_POST['arg1'] ?? '');
$arg2 = escapeshellarg($_POST['arg2'] ?? '');

if (!in_array($cmd, $allowedCommands)) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(["error" => "Command not allowed for security reasons"]));
}

// Execute the command using Hestia's CLI
$fullCommand = "sudo /usr/local/hestia/bin/" . escapeshellcmd($cmd) . " $arg1 $arg2 json";
$output = shell_exec($fullCommand);

header('Content-Type: application/json');
echo $output;