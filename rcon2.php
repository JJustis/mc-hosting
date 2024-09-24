<?php
// rcon.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

$serverName = $_POST['serverName'] ?? '';
$rconHost = $_POST['rconHost'] ?? '127.0.0.1';
$rconPort = $_POST['rconPort'] ?? 25575;
$rconPassword = $_POST['rconPassword'] ?? '';
$rconCommand = $_POST['rconCommand'] ?? '';

if (empty($serverName) || empty($rconPassword) || empty($rconCommand)) {
    die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
}

// Include the RCON class
require_once 'rcon.php';

use Thedudeguy\Rcon;

// Send the RCON command
$rcon = new Rcon($rconHost, $rconPort, $rconPassword, 3);

if ($rcon->connect()) {
    $rcon->send_command($rconCommand);
    $response = $rcon->get_response();
    $rcon->disconnect();
    echo json_encode(['success' => true, 'message' => $response]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to RCON server']);
}
?>
