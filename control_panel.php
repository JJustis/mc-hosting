<?php
// control_panel.php

function decrypt_data($encrypted_data, $key) {
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return json_decode(openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv), true);
}

$json_file = 'servers_data.json';
$servers = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];

$serverName = sanitize_input($_POST['serverName']);
$securityKeyPart2 = sanitize_input($_POST['securityKey']);

if (!isset($servers[$serverName])) {
    echo "Server not found.";
    exit;
}

$server = $servers[$serverName];
$key_part1 = $server['key_part1'];

// Combine the two parts of the key
$full_security_key = $key_part1 . $securityKeyPart2;

// Decrypt the server data
$decrypted_data = decrypt_data($server['encrypted_data'], $full_security_key);

if ($decrypted_data === false) {
    echo "Invalid security key.";
    exit;
}

// Set the other part of the key in the cookie
setcookie('key_part2', $securityKeyPart2, time() + 3600, "/");

// Display the control panel
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Control Panel for <?php echo htmlspecialchars($serverName); ?></title>
</head>
<body>
    <h1>Control Panel for <?php echo htmlspecialchars($serverName); ?></h1>
    <p>Max Players: <?php echo htmlspecialchars($decrypted_data['max_players']); ?></p>
    <!-- Add more control panel functionality here -->
</body>
</html>
