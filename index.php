<?php
/**
 * HestiaCP Monitor Bridge
 * Developed by JAMP Web Solutions
 * License: GPLv3
 */

// 1. Ρυθμίσεις Περιβάλλοντος
date_default_timezone_set('UTC');
header('Content-Type: application/json');

// 2. Διαχείριση Εισερχόμενων Δεδομένων (JSON από Flutter)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Αν τα δεδομένα δεν είναι JSON, δοκίμασε το κλασικό $_POST
if (!$data) {
    $data = $_POST;
}

// 3. Ρύθμιση Κλειδιού (Θα αντικατασταθεί από το install.sh)
$SECRET_KEY = 'JAMP_KEY_PLACEHOLDER'; 

// Εδώ αλλάζουμε το όνομα του ελέγχου για να μην το πειράξει το sed
if ($SECRET_KEY === 'NOT_CONFIGURED_YET') {
    http_response_code(500);
    die(json_encode(["error" => "API Bridge not configured."]));
}

// 4. Αυθεντικοποίηση (TOTP Token)
$clientToken = $data['app_token'] ?? '';
if (empty($clientToken)) {
    http_response_code(403);
    die(json_encode(["error" => "No token provided"]));
}

$currentTimeWindow = floor(time() / 30);
$isAuthorized = false;

// Έλεγχος σε παράθυρο 5 λεπτών για αποφυγή αποκλίσεων ώρας
for ($i = -5; $i <= 5; $i++) {
    $expectedHash = hash('sha256', $SECRET_KEY . ($currentTimeWindow + $i));
    if (hash_equals($expectedHash, $clientToken)) {
        $isAuthorized = true;
        break;
    }
}

if (!$isAuthorized) {
    http_response_code(401);
    die(json_encode(["error" => "Invalid security token"]));
}

// 5. Λίστα Επιτρεπόμενων Εντολών
$allowedCommands = [
    'v-list-sys-info',
    'v-list-sys-services',
    'v-list-web-domains',
    'v-list-mail-domains',
    'v-restart-service'
];

$cmd = $data['cmd'] ?? '';
if (!in_array($cmd, $allowedCommands)) {
    http_response_code(403);
    die(json_encode(["error" => "Command not allowed"]));
}

// 6. Καθαρισμός Ορισμάτων & Χτίσιμο Εντολής
$arg1 = (isset($data['arg1']) && $data['arg1'] !== '') ? escapeshellarg($data['arg1']) : '';
$arg2 = (isset($data['arg2']) && $data['arg2'] !== '') ? escapeshellarg($data['arg2']) : '';

// Χρησιμοποιούμε απόλυτα paths για μέγιστη ασφάλεια
$fullCommand = "/usr/bin/sudo /usr/local/hestia/bin/" . escapeshellcmd($cmd);
if ($arg1 !== '') { $fullCommand .= " $arg1"; }
if ($arg2 !== '') { $fullCommand .= " $arg2"; }

// 7. Εκτέλεση
$output = shell_exec($fullCommand . " 2>&1");

if ($output === null) {
    http_response_code(500);
    echo json_encode(["error" => "Execution failed (shell_exec)"]);
    exit;
}

// 8. Επιστροφή Αποτελέσματος
// Αν δεν είναι έγκυρο JSON (και δεν είναι restart), επιστρέφουμε σφάλμα συστήματος
if (!json_decode($output) && $cmd !== 'v-restart-service') {
    http_response_code(500);
    echo json_encode([
        "error" => "System execution error",
        "details" => trim($output)
    ]);
    exit;
}

echo $output;