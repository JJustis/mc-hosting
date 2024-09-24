<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data
    $serverName = $_POST['serverName'] ?? '';
    $maxPlayers = $_POST['maxPlayers'] ?? '';
    $securityKey = $_POST['securityKey'] ?? '';
    $pin = $_POST['pin'] ?? '';
	$serverport = $_POST['serverPort'];
$rconport = $_POST['rconPort'];
		$rconpassword = $_POST['rconPassword'];
    // Validate input
    if (empty($serverName) || empty($maxPlayers) || empty($securityKey) || empty($pin)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if (!is_numeric($maxPlayers) || $maxPlayers < 1 || $maxPlayers > 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid number of players.']);
        exit;
    }

    // Encrypt the security key
    $encryptedKey = openssl_encrypt($securityKey, 'AES-128-ECB', $pin);

    // Load existing servers data
    $servers_data = json_decode(file_get_contents('servers_data.json'), true) ?? [];

    // Check if the server already exists
    if (isset($servers_data[$serverName])) {
        echo json_encode(['success' => false, 'message' => 'A server with this name already exists.']);
        exit;
    }

    // Create server directory
    $serverDir = __DIR__ . "/minecraft/servers/$serverName";
    if (!file_exists($serverDir)) {
        if (!mkdir($serverDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create server directory.']);
            exit;
        }
    }

    // Download server JAR file
    $jar_url = "https://piston-data.mojang.com/v1/objects/59353fb40c36d304f2035d51e7d6e6baa98dc05c/server.jar";
    $jar_path = "$serverDir/server.jar";
    $ch = curl_init($jar_url);
    $fp = fopen($jar_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    if (!curl_exec($ch)) {
        echo json_encode(['success' => false, 'message' => 'Failed to download server JAR.']);
        exit;
    }
    curl_close($ch);
    fclose($fp);

    // Write server properties
    $properties = [
        "max-players=$maxPlayers",
        "gamemode=survival",
		"rcon.host=localhost",
		"enable-rcon=true",
		"server-port=$serverport",
		"rcon.port=$rconport",
		"rcon.password=$rconpassword",
        "difficulty=normal",
        "pvp=true",
        "server-port=25564",
        "generate-structures=true",
        "max-world-size=29999984"
    ];
    file_put_contents("$serverDir/server.properties", implode("\n", $properties));

    // Create start script
    $start_script = <<<EOT
@echo off
rem Limit memory to 1GB
java -Xmx1048M -Xms1024M -jar server.jar
EOT;
    file_put_contents("$serverDir/start.bat", $start_script);
    file_put_contents("$serverDir/eula.txt", "eula=true");

    // Update server data
    $servers_data[$serverName] = [
        'name' => $serverName,
        'max_players' => $maxPlayers,
        'created_at' => date('Y-m-d H:i:s'),
        'directory' => $serverDir,
        'status' => 'starting',
        'security_key' => $encryptedKey
    ];

    // Save to JSON file
    file_put_contents('servers_data.json', json_encode($servers_data, JSON_PRETTY_PRINT));

    // Return response with server data
    echo json_encode([
        'success' => true,
        'message' => "Server '$serverName' created successfully!",
        'server' => $servers_data[$serverName]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>
