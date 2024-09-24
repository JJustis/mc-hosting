<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

function sanitize_input($input) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

function load_servers_data() {
    $json_file = __DIR__ . '/servers_data.json';
    if (file_exists($json_file)) {
        $data = file_get_contents($json_file);
        return json_decode($data, true) ?? [];
    }
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverName = sanitize_input($_POST['serverName'] ?? '');
    $securityKey = $_POST['securityKey'] ?? '';

    if (empty($serverName) || empty($securityKey)) {
        echo json_encode(['success' => false, 'message' => 'Invalid server name or security key.']);
        exit;
    }

    $servers_data = load_servers_data();

    if (!isset($servers_data[$serverName])) {
        echo json_encode(['success' => false, 'message' => 'Server not found.']);
        exit;
    }

    $server = $servers_data[$serverName];
    $fullSecurityKey = $server['security_key_part1'] . $server['security_key_part2'];

    if ($securityKey === $fullSecurityKey) {
        // Generate a temporary token for the session
        $token = bin2hex(random_bytes(32));
        
        // Store the token in a session or temporary file
        session_start();
        $_SESSION['control_panel_token'] = $token;
        $_SESSION['authorized_server'] = $serverName;

        echo json_encode(['success' => true, 'message' => 'Security key verified.', 'token' => $token]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid security key.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}