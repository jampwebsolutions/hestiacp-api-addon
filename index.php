<?php
/**
 * HestiaCP Monitor Bridge
 * Developed by JAMP Web Solutions
 * License: GPLv3
 */

// Διαβάζει το input από το Flutter αν στάλθηκε ως JSON ή Stream
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data) {
    foreach ($data as $key => $value) {
        $_POST[$key] = $value;
    }
}

// The installer script will replace this placeholder with a unique 24-character key
$SECRET_KEY = 'JAMP_KEY_PLACEHOLDER'; 

// Basic security check to ensure the installer has run
if ($SECRET_KEY === 'JAMP_KEY_PLACEHOLDER') {
    header('HTTP/1.1 500 Internal Server Error');
    die(json_encode(["error" => "API Bridge not configured. Please run the installer script."]));
}

// 1. Authenticate the request using a time-based token (TOTP)
if (!isset($_POST['app_token']) || empty($_POST['app_token'])) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(["error" => "No token provided"]));
}

$clientToken = $_POST['app_token'];
$currentTimeWindow = floor(time() / 30);
$isAuthorized = false;

// Validate token within a 5-minute window to account for server/mobile time drift
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

// 2. Define safe HestiaCP commands (Read-only + Restart)
$allowedCommands = [
    'v-list-sys-info',
    'v-list-sys-services',
    'v-list-web-domains',
    'v-list-mail-domains',
    'v-restart-service'
];

$cmd = $_POST['cmd'] ?? '';
if (!in_array($cmd, $allowedCommands)) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(["error" => "Command not allowed for security reasons"]));
}

// 3. Sanitize arguments and build the CLI command
$arg1 = (isset($_POST['arg1']) && $_POST['arg1'] !== '') ? escapeshellarg($_POST['arg1']) : '';
$arg2 = (isset($_POST['arg2']) && $_POST['arg2'] !== '') ? escapeshellarg($_POST['arg2']) : '';

$fullCommand = "/usr/bin/sudo /usr/local/hestia/bin/" . escapeshellcmd($cmd);
if ($arg1 !== '') { $fullCommand .= " $arg1"; }
if ($arg2 !== '') { $fullCommand .= " $arg2"; }

// 4. Execute and return JSON output
$output = shell_exec($fullCommand . " 2>&1");

// Αν το output είναι κενό, σημαίνει ότι η shell_exec είναι απενεργοποιημένη στην PHP
if ($output === null) {
    echo json_encode(["error" => "shell_exec is disabled in php.ini"]);
    exit;
}

// Αν το output δεν είναι έγκυρο JSON, σημαίνει ότι το sudo έβγαλε σφάλμα κειμένου
if (!json_decode($output) && $cmd !== 'v-restart-service') {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => "System Error",
        "raw_output" => $output,
        "executed_command" => $fullCommand,
        "current_user" => getcurrentuser()
    ]);
    exit;
}

header('Content-Type: application/json');
echo $output;