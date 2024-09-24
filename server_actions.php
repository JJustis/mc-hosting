<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Function to sanitize input
function sanitize_input($input) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

// Function to load servers data
function load_servers_data() {
    $json_file = __DIR__ . '/servers_data.json';
    if (file_exists($json_file)) {
        $data = file_get_contents($json_file);
        return json_decode($data, true) ?? [];
    }
    return [];
}

// Function to save servers data
function save_servers_data($data) {
    $json_file = __DIR__ . '/servers_data.json';
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $serverName = sanitize_input($_POST['server'] ?? '');

    if (empty($action) || empty($serverName)) {
        echo json_encode(['success' => false, 'message' => 'Invalid action or server name.']);
        exit;
    }

    $servers_data = load_servers_data();

    if (!isset($servers_data[$serverName])) {
        echo json_encode(['success' => false, 'message' => 'Server not found.']);
        exit;
    }

    $server = $servers_data[$serverName];
    $serverDir = $server['directory'];

    switch ($action) {
        case 'start':
            if ($server['status'] === 'running') {
                echo json_encode(['success' => false, 'message' => 'Server is already running.']);
            } else {
                // Start the server
                exec("start /B $serverDir\\start.bat", $output, $return_var);
                if ($return_var === 0) {
                    $servers_data[$serverName]['status'] = 'running';
                    save_servers_data($servers_data);
                    echo json_encode(['success' => true, 'message' => 'Server started successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to start server.']);
                }
            }
            break;

        case 'stop':
            if ($server['status'] === 'stopped') {
                echo json_encode(['success' => false, 'message' => 'Server is already stopped.']);
            } else {
                // Stop the server (you may need to implement a proper way to stop the Minecraft server)
                // This is a placeholder implementation
                exec("taskkill /F /FI \"WINDOWTITLE eq $serverName\"", $output, $return_var);
                if ($return_var === 0) {
                    $servers_data[$serverName]['status'] = 'stopped';
                    save_servers_data($servers_data);
                    echo json_encode(['success' => true, 'message' => 'Server stopped successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to stop server.']);
                }
            }
            break;

        case 'restart':
            // Stop the server
            exec("taskkill /F /FI \"WINDOWTITLE eq $serverName\"", $output, $return_var);
            // Start the server
            exec("start /B $serverDir\\start.bat", $output, $return_var);
            if ($return_var === 0) {
                $servers_data[$serverName]['status'] = 'running';
                save_servers_data($servers_data);
                echo json_encode(['success' => true, 'message' => 'Server restarted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restart server.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}